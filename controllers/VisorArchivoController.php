<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';

class VisorArchivoController
{
    public function ver(): void
    {
        iniciarSesion();
        $usuario    = requiereLogin();
        $archivo    = basename((string) ($_GET['archivo'] ?? ''));
        $rutaFisica = __DIR__ . '/../uploads/asistencia/' . $archivo;

        if ($archivo === '' || !file_exists($rutaFisica)) {
            http_response_code(404);
            die("Error: El archivo no existe o el acceso ha sido denegado.");
        }

        $rolAdmin = in_array($usuario['rol'], ['Administrador', 'Director'], true);

        if (!$rolAdmin) {
            $stmt = db()->prepare(
                "SELECT id_documento FROM documentos_asistencia WHERE archivo = ? AND id_docente = ? LIMIT 1"
            );
            $stmt->execute([$archivo, (int) $usuario['id_usuario']]);

            if (!$stmt->fetch()) {
                http_response_code(403);
                die("Error: El archivo no existe o el acceso ha sido denegado.");
            }
        }

        $ext   = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
        $filas = $this->parsearArchivo($rutaFisica, $ext);

        require __DIR__ . '/../views/visor_archivo_v.php';
    }

    // ── Eliminar namespaces XML para que SimpleXML navegue sin children() ──
    private function sinNamespaces(string $xml): string
    {
        $xml = preg_replace('/\s+xmlns(?::\w+)?="[^"]*"/', '', $xml);
        $xml = preg_replace('/<(\/?)\w+:/', '<$1', $xml);
        return $xml;
    }

    private function parsearArchivo(string $ruta, string $ext): array
    {
        if ($ext === 'csv') {
            return $this->parsearCSV($ruta);
        }
        if (in_array($ext, ['xlsx', 'xls'], true)) {
            return $this->parsearXLSX($ruta);
        }
        return [];
    }

    private function parsearCSV(string $ruta): array
    {
        $filas  = [];
        $handle = fopen($ruta, 'r');
        if ($handle === false) {
            return [];
        }
        $primera = fgets($handle);
        rewind($handle);
        $sep = substr_count((string) $primera, ';') > substr_count((string) $primera, ',') ? ';' : ',';

        while (($datos = fgetcsv($handle, 4000, $sep)) !== false) {
            $filas[] = array_map('strval', $datos);
        }
        fclose($handle);
        return $filas;
    }

    private function parsearXLSX(string $ruta): array
    {
        if (!class_exists('ZipArchive')) {
            error_log('VisorArchivo: ZipArchive no disponible');
            return [];
        }

        $zip    = new ZipArchive();
        $status = $zip->open($ruta);
        if ($status !== true) {
            error_log("VisorArchivo: no se abrió el ZIP (code=$status)");
            return [];
        }

        // ── 1. Cadenas compartidas ──────────────────────────────────────
        $cadenas = [];
        $ssXml   = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            libxml_use_internal_errors(true);
            $ss = simplexml_load_string($this->sinNamespaces($ssXml));
            if ($ss) {
                foreach ($ss->si as $si) {
                    if (isset($si->t)) {
                        $cadenas[] = (string) $si->t;
                    } else {
                        $texto = '';
                        foreach (($si->r ?? []) as $r) {
                            $texto .= (string) ($r->t ?? '');
                        }
                        $cadenas[] = $texto;
                    }
                }
            }
        }

        // ── 2. Localizar la primera hoja ────────────────────────────────
        $sheetPath = null;
        $relXml    = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($relXml !== false) {
            libxml_use_internal_errors(true);
            $rels = simplexml_load_string($this->sinNamespaces($relXml));
            if ($rels) {
                foreach ($rels->Relationship as $rel) {
                    if (str_contains((string) $rel['Type'], 'worksheet')) {
                        $target = (string) $rel['Target'];
                        $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . $target;
                        break;
                    }
                }
            }
        }

        if ($sheetPath === null) {
            $sheetPath = 'xl/worksheets/sheet1.xml';
        }

        $hojaXml = $zip->getFromName($sheetPath);

        // Fallback: escanear el ZIP
        if ($hojaXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nombre = $zip->getNameIndex($i);
                if ($nombre !== false
                    && str_contains($nombre, 'worksheets/')
                    && str_ends_with($nombre, '.xml')) {
                    $hojaXml = $zip->getFromIndex($i);
                    if ($hojaXml !== false) break;
                }
            }
        }

        $zip->close();

        if ($hojaXml === false) {
            error_log('VisorArchivo: no se encontró ninguna hoja en el XLSX');
            return [];
        }

        // ── 3. Parsear la hoja ──────────────────────────────────────────
        libxml_use_internal_errors(true);
        $hoja = simplexml_load_string($this->sinNamespaces($hojaXml));
        if (!$hoja || !isset($hoja->sheetData)) {
            error_log('VisorArchivo: sheetData no encontrado tras strip de namespaces');
            return [];
        }

        $rawFilas = [];
        $maxCol   = 0;

        foreach ($hoja->sheetData->row as $fila) {
            $idxFila = (int) $fila['r'] - 1;
            $celdas  = [];

            foreach ($fila->c as $celda) {
                preg_match('/^([A-Z]+)\d+$/i', (string) $celda['r'], $m);
                if (!$m) continue;

                $colStr = strtoupper($m[1]);
                $colIdx = 0;
                for ($i = 0; $i < strlen($colStr); $i++) {
                    $colIdx = $colIdx * 26 + (ord($colStr[$i]) - 64);
                }
                $colIdx--;
                $maxCol = max($maxCol, $colIdx);

                $tipo = (string) ($celda['t'] ?? '');
                $val  = isset($celda->v) ? (string) $celda->v : '';

                if ($tipo === 's') {
                    $val = $cadenas[(int) $val] ?? $val;
                } elseif ($tipo === 'inlineStr') {
                    $val = (string) ($celda->is->t ?? '');
                }

                $celdas[$colIdx] = $val;
            }

            if (!empty($celdas)) {
                $rawFilas[$idxFila] = $celdas;
            }
        }

        if (empty($rawFilas)) {
            error_log('VisorArchivo: ninguna fila con datos encontrada en sheetData');
            return [];
        }

        ksort($rawFilas);
        $resultado = [];
        foreach ($rawFilas as $celdas) {
            $fila = [];
            for ($c = 0; $c <= $maxCol; $c++) {
                $fila[] = $celdas[$c] ?? '';
            }
            $resultado[] = $fila;
        }

        return $resultado;
    }
}
