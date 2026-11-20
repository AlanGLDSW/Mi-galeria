<?php
class ValidarUsuario
{
    /**
     * @param array $d        datos del formulario
     * @param int   $id       id del usuario que se edita (0 si es nuevo)
     * @param bool  $claveObligatoria  true al registrarse
     * @return array [datos limpios, errores por campo]
     */
    public static function revisar(array $d, int $id = 0, bool $claveObligatoria = false): array
    {
        $modelo = new UsuarioModel();
        $e = [];

        $limpio = [
            'nombre'  => trim(preg_replace('/\s+/u', ' ', (string) ($d['nombre'] ?? ''))),
            'usuario' => trim((string) ($d['usuario'] ?? '')),
            'correo'  => mb_strtolower(trim((string) ($d['correo'] ?? ''))),
            'clave'   => (string) ($d['clave'] ?? ''),
        ];

        $largo = mb_strlen($limpio['nombre']);
        if ($largo < 3 || $largo > 100) {
            $e['nombre'] = 'Escribe tu nombre (de 3 a 100 caracteres).';
        }

        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $limpio['usuario'])) {
            $e['usuario'] = 'De 3 a 30 caracteres: letras, números, punto, guion o guion bajo.';
        } elseif ($modelo->existe('usuario', $limpio['usuario'], $id)) {
            $e['usuario'] = 'Ese nombre de usuario ya está en uso.';
        }

        if (!filter_var($limpio['correo'], FILTER_VALIDATE_EMAIL) || mb_strlen($limpio['correo']) > 150) {
            $e['correo'] = 'Escribe un correo válido.';
        } elseif ($modelo->existe('correo', $limpio['correo'], $id)) {
            $e['correo'] = 'Ese correo ya está registrado.';
        }

        $clave = $limpio['clave'];
        if ($claveObligatoria || $clave !== '') {
            if (strlen($clave) < 8 || !preg_match('/[A-Za-z]/', $clave) || !preg_match('/\d/', $clave)) {
                $e['clave'] = 'Mínimo 8 caracteres, con al menos una letra y un número.';
            } elseif (strlen($clave) > 72) {
                $e['clave'] = 'La contraseña no puede pasar de 72 caracteres.';
            } elseif (array_key_exists('confirmar', $d) && $clave !== $d['confirmar']) {
                $e['confirmar'] = 'Las contraseñas no coinciden.';
            }
        }

        return [$limpio, $e];
    }
}
