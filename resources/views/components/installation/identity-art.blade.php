@props(['variant' => 'identity'])
<div class="installation-server-art installation-identity-art" aria-hidden="true">
    <div class="installation-server-art__halo"></div><div class="installation-server-art__orbit"></div>
    <span class="installation-server-art__star installation-server-art__star--one">✦</span><span class="installation-server-art__star installation-server-art__star--two">✦</span>
    @if ($variant === 'install')
        <div class="installation-gift"><i></i><span></span></div><span class="installation-server-art__check"><x-vuero.icon name="check" size="25" /></span>
    @else
        <div class="installation-site-art"><div class="installation-site-art__bar">•••</div><x-vuero.icon name="globe" size="47" /><span><i></i><i></i><i></i></span></div>
    @endif
</div>
