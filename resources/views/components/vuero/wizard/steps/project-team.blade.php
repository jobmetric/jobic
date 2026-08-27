@props(['teammates'])

<section class="wizard-step" data-wizard-step="5" aria-labelledby="wizard-step-5-title" hidden>
    <div class="wizard-step__content">
        <header class="wizard-step__title"><h1 id="wizard-step-5-title">Who will be working on this project?</h1><p>Start by adding members to your team</p></header>
        <div class="wizard-placeholder wizard-team-placeholder" data-team-placeholder>
            <img src="{{ asset('vuero/images/illustrations/wizard/team-placeholder.svg') }}" alt=""><h2>Invite People</h2><p>You can already start adding files to your project if you have them handy. But don't worry, you'll be able to add and manage files later.</p><button type="button" data-show-team>Add Members</button>
        </div>
        <div class="wizard-team" data-wizard-team hidden>
            <header><div class="wizard-owner-avatar"><img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt="Erik Kovalsky"><span>🇺🇸</span></div><h2>Erik Kovalsky</h2><p>You are the project owner</p><label class="wizard-field wizard-field--icon"><x-vuero.icon name="search" size="18" /><input type="search" placeholder="Search teammates..." autocomplete="off" data-team-search></label></header>
            <div class="wizard-team__results" data-team-results hidden>
                @foreach ($teammates as $teammate)
                    <button type="button" data-team-option data-team-name="{{ $teammate['name'] }}" data-team-image="{{ asset('vuero/images/wizard/people/'.$teammate['image']) }}"><img src="{{ asset('vuero/images/wizard/people/'.$teammate['image']) }}" alt=""><span><strong>Invite</strong><small>{{ $teammate['name'] }}</small></span><i><x-vuero.icon name="plus" size="15" /></i></button>
                @endforeach
            </div>
            <div class="wizard-team__members" data-team-members><p data-team-empty>No team members yet</p></div>
        </div>
    </div>
</section>
