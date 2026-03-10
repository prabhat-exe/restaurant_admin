@php
    $title = 'POS';
    $panelName = 'Restaurant Panel';
    $heading = 'POS';
    $subheading = '';
    $logoutRoute = 'restaurant.logout';
    $navLinks = [
        ['label' => 'Items', 'route' => 'restaurant.dashboard'],
        ['label' => 'Orders', 'route' => 'restaurant.orders', 'active' => 'restaurant.orders*'],
        ['label' => 'POS', 'route' => 'restaurant.pos'],
        ['label' => 'Menu Import', 'route' => 'menu.import.form'],
    ];
@endphp

@extends('layouts.panel')
@section('content')

 

@endsection
