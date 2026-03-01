<?php
$request = Illuminate\Http\Request::create('/admin/charter', 'GET', ['location' => 'LP', 'date' => '2026-02-22']);
$controller = new App\Http\Controllers\CharterController();
$inertia = clone $controller->index($request);
echo var_export($inertia->toResponse($request)->original['page']['props']['reservations'], true);
