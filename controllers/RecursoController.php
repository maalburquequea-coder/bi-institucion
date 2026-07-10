<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../services/AIService.php';

class RecursoController
{
    private const AREAS_VALIDAS = [
        'Matematica', 'Comunicacion', 'Ciencias', 'EPT', 'Historia',
        'Ingles', 'Arte', 'Educacion Fisica', 'Religion', 'Tutoria',
    ];

    private const GRADOS_VALIDOS = [
        '1ro de Primaria', '2do de Primaria', '3ro de Primaria',
        '4to de Primaria', '5to de Primaria', '6to de Primaria',
        '1ro de Secundaria', '2do de Secundaria', '3ro de Secundaria',
        '4to de Secundaria', '5to de Secundaria',
    ];

    public function recurso(): void
    {
        iniciarSesion();
        $usuario = requiereLogin();

        $areaInput  = (string) ($_GET['area']  ?? '');
        $gradoInput = (string) ($_GET['grado'] ?? '');

        $area  = in_array($areaInput,  self::AREAS_VALIDAS,  true) ? $areaInput  : 'Matematica';
        $grado = in_array($gradoInput, self::GRADOS_VALIDOS, true) ? $gradoInput : '1ro de Secundaria';

        $contenido = AIService::generarContenidoEducativo($area, $grado);

        require __DIR__ . '/../views/recurso_v.php';
    }
}
