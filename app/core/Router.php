<?php
class Router
{
    public function __construct(private array $rutas)
    {
    }

    public function despachar(string $ruta, string $metodo): void
    {
        $ruta = trim($ruta, '/');
        $clave = strtoupper($metodo) . ' ' . $ruta;

        if (!isset($this->rutas[$clave])) {
            $otro = (strtoupper($metodo) === 'GET' ? 'POST ' : 'GET ') . $ruta;
            $codigo = isset($this->rutas[$otro]) ? 405 : 404;
            (new Controller())->error($codigo);
            return;
        }

        [$clase, $accion] = $this->rutas[$clave];
        (new $clase())->$accion();
    }
}
