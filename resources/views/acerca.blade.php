@extends('layouts.app')

@section('titulo', 'Sobre nosotros')

@section('contenido')
    <x-encabezado icono="ti-info-circle" titulo="Sobre nosotros">    </x-encabezado>

    <div class="panel">
        <div class="panel-titulo">
            <h2><i class="ti ti-info-circle"></i> Acerca de la plataforma</h2>
        </div>

        <div class="panel-contenido">
            <p>Esta plataforma de monitorización IoT modular es un proyecto desarrollado por la Universidad Politécnica de Madrid (UPM) y el Instituto Nacional de Investigación y Capacitación de Telecomunicaciones (INICTEL-UNI) en Perú.</p>
            <p>Su objetivo es proporcionar una herramienta eficiente para la gestión y monitorización de nodos IoT, permitiendo a los usuarios visualizar datos en tiempo real, configurar parámetros y gestionar proyectos y sitios asociados.</p>
            <p>El desarrollo de esta plataforma se ha centrado en la modularidad, escalabilidad y facilidad de uso, asegurando que pueda adaptarse a diferentes necesidades y entornos de implementación.</p>
            <p>Para más información, visite los sitios web oficiales de las instituciones involucradas:</p>
            <ul>
                <li><a href="https://www.upm.es" target="_blank" rel="noopener">Universidad Politécnica de Madrid (UPM)</a></li>
                <li><a href="https://www.inictel-uni.edu.pe" target="_blank" rel="noopener">INICTEL-UNI</a></li>
            </ul>
        </div>
    </div>
@endsection