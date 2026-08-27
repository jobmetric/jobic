@props(['tools'])

<section class="wizard-step" data-wizard-step="6" aria-labelledby="wizard-step-6-title" hidden>
    <div class="wizard-step__content">
        <header class="wizard-step__title"><h1 id="wizard-step-6-title">What tools will you be using?</h1><p>Choose a set of tools that you'll be using in this project.</p></header>
        <div class="wizard-tools">
            @foreach ($tools as $tool)
                <label class="wizard-tool-card"><input type="checkbox" value="{{ $tool['name'] }}" data-tool-name="{{ $tool['name'] }}" data-tool-description="{{ $tool['description'] }}" data-tool-image="{{ asset('vuero/images/wizard/tools/'.$tool['image']) }}"><span><img src="{{ asset('vuero/images/wizard/tools/'.$tool['image']) }}" alt=""><i><strong>{{ $tool['name'] }}</strong><small>{{ $tool['description'] }}</small></i><b><x-vuero.icon name="check" size="15" /></b></span></label>
            @endforeach
        </div>
    </div>
</section>
