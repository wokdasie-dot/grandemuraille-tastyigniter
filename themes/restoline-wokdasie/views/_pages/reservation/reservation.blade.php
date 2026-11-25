---
title: "Réservation | La Grande Muraille de Chine"
layout: default
permalink: ':location/reservation'
bodyClass: page-booking-redirect
---
@php
    abort(redirect()->to(url('/').'#reservation', 301));
@endphp
