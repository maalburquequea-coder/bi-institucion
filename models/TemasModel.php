<?php
declare(strict_types=1);

class TemasModel
{
    public function __construct(private PDO $db) {}

    public function guardar(int $idDocente, int $grado, string $seccion, string $area, int $bimestre, int $unidad, array $sesiones): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO temas_docente (id_docente, grado, seccion, area, bimestre, unidad, sesion_1, sesion_2, sesion_3, sesion_4)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                sesion_1 = VALUES(sesion_1),
                sesion_2 = VALUES(sesion_2),
                sesion_3 = VALUES(sesion_3),
                sesion_4 = VALUES(sesion_4),
                actualizado = NOW()
        ");
        $stmt->execute([
            $idDocente, $grado, $seccion, $area, $bimestre, $unidad,
            $sesiones[0] ?? '', $sesiones[1] ?? '', $sesiones[2] ?? '', $sesiones[3] ?? '',
        ]);
    }

    public function obtenerPorDocente(int $idDocente): array
    {
        $stmt = $this->db->prepare("
            SELECT grado, seccion, area, bimestre, unidad,
                   sesion_1, sesion_2, sesion_3, sesion_4, actualizado
            FROM temas_docente
            WHERE id_docente = ?
            ORDER BY grado, seccion, area, bimestre, unidad
        ");
        $stmt->execute([$idDocente]);
        return $stmt->fetchAll();
    }

    public function obtenerParaPrompt(int $idDocente, int $grado, string $seccion, string $area): string
    {
        $stmt = $this->db->prepare("
            SELECT bimestre, unidad, sesion_1, sesion_2, sesion_3, sesion_4
            FROM temas_docente
            WHERE id_docente = ? AND grado = ? AND seccion = ? AND area = ?
            ORDER BY bimestre, unidad
        ");
        $stmt->execute([$idDocente, $grado, $seccion, $area]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return '';
        }

        $partes = [];
        foreach ($rows as $r) {
            $unidadGlobal = (($r['bimestre'] - 1) * 2) + $r['unidad'];
            $sesiones = array_filter([
                $r['sesion_1'], $r['sesion_2'], $r['sesion_3'], $r['sesion_4'],
            ]);
            if (!empty($sesiones)) {
                $partes[] = "Bimestre {$r['bimestre']} Unidad {$unidadGlobal}: " . implode(', ', $sesiones);
            }
        }

        return implode('; ', $partes);
    }
}
