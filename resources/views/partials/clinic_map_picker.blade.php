{{--
  Clinic location map picker (Leaflet + OSM under the hood).
  User-facing copy stays plain language — no map-tech jargon.
--}}
@php
    $mapId = $mapId ?? 'clinic-map';
    $latInputId = $latInputId ?? 'latitude';
    $lngInputId = $lngInputId ?? 'longitude';
    $addressInputId = $addressInputId ?? null;
    $prefix = $prefix ?? $mapId;
    $searchId = $prefix . '-search';
    $searchBtnId = $prefix . '-search-btn';
    $clearBtnId = $prefix . '-clear-btn';
    $statusId = $prefix . '-status';
    $hideNativeInputs = $hideNativeInputs ?? true;
    $latModel = $latModel ?? null;
    $lngModel = $lngModel ?? null;
    $omitName = $omitName ?? false;
@endphp

<div class="lumi-clinic-map-picker" data-map-id="{{ $mapId }}">
    <label class="lumi-map-label">Clinic location on map</label>
    <p class="lumi-map-hint">
        Search for the clinic address, or tap the map to place a pin. Drag the pin to fine-tune.
    </p>
    <div class="lumi-map-search">
        <input
            type="search"
            id="{{ $searchId }}"
            class="lumi-map-search-input"
            placeholder="Search address (e.g. Lipa City, Batangas)"
            autocomplete="off"
        >
        <button type="button" id="{{ $searchBtnId }}" class="lumi-map-btn lumi-map-btn--primary">Search</button>
        <button type="button" id="{{ $clearBtnId }}" class="lumi-map-btn">Remove pin</button>
    </div>
    <div id="{{ $mapId }}" class="lumi-clinic-map" role="img" aria-label="Clinic location map"></div>
    <p id="{{ $statusId }}" class="lumi-map-status" aria-live="polite"></p>

    {{-- Coordinates stay in the form for save; not shown to users --}}
    <input
        type="hidden"
        id="{{ $latInputId }}"
        @unless($omitName) name="{{ $latInputName ?? 'latitude' }}" @endunless
        value="{{ $initialLat ?? '' }}"
        @if($latModel) x-model="{{ $latModel }}" @endif
    >
    <input
        type="hidden"
        id="{{ $lngInputId }}"
        @unless($omitName) name="{{ $lngInputName ?? 'longitude' }}" @endunless
        value="{{ $initialLng ?? '' }}"
        @if($lngModel) x-model="{{ $lngModel }}" @endif
    >
</div>
