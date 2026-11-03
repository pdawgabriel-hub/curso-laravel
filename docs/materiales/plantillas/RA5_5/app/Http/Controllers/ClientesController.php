<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ClientesController extends Controller
{
    // Datos de prueba hasta el Bloque 6: pega aquí el contenido de array_clientes.php
    private array $arrayClientes = [
        1 => ['nombre' => 'Juan', 'apellidos' => 'Pérez López', 'nif' => '12345678Z', 'direccion' => 'Calle Mayor 12', 'cod_postal' => '28013', 'telefono' => '600111222'],
        // ...
    ];

    public function index(): View
    {
        return view('clientes.index', ['clientes' => $this->arrayClientes]);
    }

    public function show(int $id): View
    {
        abort_unless(isset($this->arrayClientes[$id]), 404);

        return view('clientes.show', ['cliente' => $this->arrayClientes[$id], 'id' => $id]);
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function edit(int $id): View
    {
        abort_unless(isset($this->arrayClientes[$id]), 404);

        return view('clientes.edit', ['cliente' => $this->arrayClientes[$id], 'id' => $id]);
    }
}
