<?php
class InicioController extends Controller
{
    public function index(): void
    {
        $this->redirigir(Session::usuario() ? 'galeria' : 'login');
    }
}
