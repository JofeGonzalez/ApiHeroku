<?php

const PATH_IMAGENES_PEDIDOS = './imagenesPedidos';
const PATH_UPLOADS = './uploads';

CONST TIMEZONE = 'America/Argentina/Buenos_Aires';

const PERFILES = [
    'admin',
    'bartender',
    'cervecero',
    'cocinero',
    'mozo',
    'socio'
];

const ESTADOS_MESA = [
    0 => "con cliente esperando pedido",
    1 => "con cliente comiendo",
    2 => "reservada",
    3 => "cerrada"
];

const ESTADOS_PEDIDO = [
    0 => "pedido",
    1 => "en preparacion",
    2 => "listo para servir",
    3 => "cancelado",
    4 => "esperando pago",
    5 => "finalizado"
];

const ESTADOS_CONSUMIBLE = [
    0 => "pedido",
    1 => "en preparacion",
    2 => "cancelado",
    3 => "terminado"
];

const TIPOS_CONSUMIBLE = [
    "bebida",
    "cerveza",
    "plato",
    "postre",
    "trago",
    "vino"
];

