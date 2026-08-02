{{-- Design system head assets — LUMI brand tokens + fonts --}}
<link rel="icon" type="image/png" href="{{ asset('assets/lumi_app_icon.png') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/lumi_app_icon.png') }}">
<style>
@font-face {
  font-family: 'SuperJoyful';
  src: url('{{ asset('fonts/SuperJoyful.ttf') }}') format('truetype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
@font-face {
  font-family: 'ClanPro';
  src: url('{{ asset('fonts/ClanPro-Regular.ttf') }}') format('truetype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
@font-face {
  font-family: 'ClanPro';
  src: url('{{ asset('fonts/ClanPro-Medium.ttf') }}') format('truetype');
  font-weight: 500;
  font-style: normal;
  font-display: swap;
}
</style>
<link rel="stylesheet" href="{{ asset('css/lumi-ds.css') }}">
