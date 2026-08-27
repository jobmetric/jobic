<section class="wizard-step is-active" data-wizard-step="1" aria-labelledby="wizard-step-1-title">
    <div class="wizard-step__content">
        <header class="wizard-step__title"><h1 id="wizard-step-1-title">Select a project type</h1></header>
        <div class="wizard-types">
            @foreach ([
                ['UI/UX Design', 'type-1.svg'],
                ['Web Development', 'type-2.svg'],
                ['Marketing', 'type-3.svg'],
            ] as [$type, $image])
                <article class="wizard-type-card">
                    <img src="{{ asset('vuero/images/illustrations/wizard/'.$image) }}" alt="">
                    <h2>{{ $type }}</h2>
                    <p>Some short explanation about the type goes here.</p>
                    <button class="wizard-button wizard-button--primary wizard-type-card__continue" type="button" data-project-type="{{ $type }}">Continue</button>
                    <a class="wizard-type-card__learn" href="#" data-wizard-learn-more="{{ $type }}">Or Learn More</a>
                </article>
            @endforeach
        </div>
    </div>
</section>
