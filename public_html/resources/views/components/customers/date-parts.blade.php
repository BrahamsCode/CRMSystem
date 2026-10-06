{{-- Año / mes / día por separado, como en el sistema legacy.
     El sufijo «!» fuerza el ancho sobre el w-full base del input. --}}
<div class="flex items-center gap-2">
    <x-ui.input type="number" aria-label="Año" placeholder="Año" class="w-25!" />
    <x-ui.input type="number" aria-label="Mes" placeholder="Mes" class="w-19!" />
    <x-ui.input type="number" aria-label="Día" placeholder="Día" class="w-19!" />
</div>
