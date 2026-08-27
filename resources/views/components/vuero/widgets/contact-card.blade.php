@props(['squared' => false, 'reversed' => false])

<article {{ $attributes->class(['widget-card', 'widget-contact', 'is-reversed' => $reversed]) }} data-widget-card>
    <div class="widget-contact__main">
        <x-vuero.widgets.avatar src="/vuero/images/admin/avatars/vuero-1.svg" name="Erik K." :squared="$squared" :size="54" />
        <div><h2>Erik K.</h2><strong>Vuero Ltd.</strong><p>Product Manager</p></div>
    </div>
    <div class="widget-contact__meta"><span><x-vuero.icon name="location" size="13" />Los Angeles, CA</span><span><x-vuero.icon name="phone" size="13" />+1 444-5156</span></div>
    <a href="mailto:erikkovalsky@vuero.io">erikkovalsky@vuero.io</a>
</article>
