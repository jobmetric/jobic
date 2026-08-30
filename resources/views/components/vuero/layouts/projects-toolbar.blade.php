@props(['members' => []])

<div class="projects-toolbar">
    <div class="field">
        <div class="control has-icon">
            <input class="input" type="search" placeholder="Search again..." data-projects-search aria-label="Search projects">
            <span class="form-icon"><x-vuero.icon name="search" size="17" /></span>
        </div>
    </div>

    <x-vuero.layouts.flex-avatar-stack :avatars="$members" :limit="6" />

    <button type="button" class="button v-button is-primary is-raised is-bold" data-projects-new>
        <span>New Project</span>
    </button>
</div>
