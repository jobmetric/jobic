@php
    $filterGroups = [
        'Type of Employment' => [['full-time', 'Full Time Jobs', 56], ['part-time', 'Part Time Jobs', 43], ['remote', 'Remote Jobs', 24], ['internship', 'Internship Jobs', 27], ['contract', 'Contract', 76], ['training', 'Training Jobs', 28]],
        'Seniority Level' => [['student', 'Student Level', 98], ['entry', 'Entry Level', 44], ['mid', 'Mid Level', 35], ['senior', 'Senior Level', 29], ['director', 'Directors', 26]],
        'Salary Range' => [['700', '$700 - $1000', 49], ['1000', '$1000 - $1200', 67], ['1200', '$1200 - $1400', 24], ['1500', '$1500 - $1800', 27], ['2000', '$2000 - $3000', 76], ['3000', '$3000 - $4000', 22], ['4000', '$4000 - $5000', 18]],
    ];
    $jobs = [
        ['brand' => 'airbnb', 'title' => 'UI / UX Designer', 'type' => 'full-time', 'level' => 'senior', 'salary' => 3000],
        ['brand' => 'slack', 'title' => 'Sr. Product Designer', 'type' => 'part-time', 'level' => 'senior', 'salary' => 4000],
        ['brand' => 'github', 'title' => 'UI / UX Designer', 'type' => 'remote', 'level' => 'mid', 'salary' => 2000],
        ['brand' => 'google', 'title' => 'Product Designer', 'type' => 'full-time', 'level' => 'mid', 'salary' => 3000],
        ['brand' => 'facebook', 'title' => 'UI / UX Designer', 'type' => 'contract', 'level' => 'entry', 'salary' => 1500],
        ['brand' => 'tnw', 'title' => 'Web Developer', 'type' => 'training', 'level' => 'student', 'salary' => 1000],
        ['brand' => 'dribbble', 'title' => 'UI Designer', 'type' => 'internship', 'level' => 'entry', 'salary' => 1200],
        ['brand' => 'atlassian', 'title' => 'UI / UX Designer', 'type' => 'full-time', 'level' => 'senior', 'salary' => 4000],
        ['brand' => 'gitlab', 'title' => 'UI / UX Designer', 'type' => 'remote', 'level' => 'mid', 'salary' => 3000],
    ];
@endphp

<div class="business-dashboard jobs-dashboard" data-business-dashboard="jobs">
    <form class="jobs-search-menu" data-job-search-form>
        <label class="jobs-search-menu__query"><x-vuero.icon name="search" size="18" /><input type="search" placeholder="Search for jobs..." data-job-query></label>
        <label><x-vuero.icon name="location" size="18" /><input type="text" value="Los Angeles, CA" aria-label="Location" data-job-location></label>
        <label><x-vuero.icon name="briefcase" size="18" /><input type="text" placeholder="Job Type" data-job-type-query></label>
        <label><x-vuero.icon name="dollar" size="18" /><input type="text" placeholder="Salary Range" data-job-salary-query></label>
        <button type="submit">Search</button>
    </form>

    <div class="jobs-main-container">
        <aside class="jobs-filter-column">
            <section class="jobs-alert-card">
                <h2>Create Job Alert</h2><p>Create a job alert now and never miss a job</p>
                <label class="business-search-input"><x-vuero.icon name="briefcase" size="17" /><input type="text" placeholder="Keywords" data-job-alert-keywords></label>
                <button type="button" class="vuero-button is-primary" data-job-alert>Create Job Alert</button>
            </section>
            <div class="jobs-filter-groups" data-jobs-filters>
                @foreach ($filterGroups as $heading => $options)
                    <fieldset data-filter-group="{{ Str::slug($heading) }}">
                        <legend>{{ $heading }}</legend>
                        @foreach ($options as $option)
                            <label><input type="checkbox" value="{{ $option[0] }}"><i></i><span>{{ $option[1] }}</span><b>{{ $option[2] }}</b></label>
                        @endforeach
                    </fieldset>
                @endforeach
            </div>
        </aside>

        <main class="jobs-results-column">
            <header class="jobs-results-heading"><h1>Showing <span data-job-count>{{ count($jobs) }}</span> Jobs</h1><button type="button" class="business-action-link" data-jobs-view-all>View All</button></header>
            <section class="jobs-card-grid" data-jobs-results>
                @foreach ($jobs as $job)
                    <article class="dashboard-card jobs-card" data-job-card data-type="{{ $job['type'] }}" data-level="{{ $job['level'] }}" data-salary="{{ $job['salary'] }}" data-search="{{ strtolower($job['brand'].' '.$job['title']) }}">
                        <img src="{{ asset('vuero/images/admin/business/brands/'.$job['brand'].'.svg') }}" alt="{{ ucfirst($job['brand']) }}">
                        <h2>{{ $job['title'] }}</h2>
                        <p>The User Experience Designer position exists to create compelling and digital user experience through excellent design...</p>
                        <div class="jobs-card__tags"><span>Full Time</span><span>Min. 1 Year</span><span>Senior Level</span></div>
                        <footer><button type="button" class="vuero-button is-primary" data-job-apply>Apply Now</button><button type="button" class="vuero-button" data-job-message>Messages</button></footer>
                    </article>
                @endforeach
            </section>
            <p class="business-empty-state jobs-empty" data-jobs-empty hidden>No jobs match the selected filters.</p>
        </main>
    </div>

    <button type="button" class="jobs-mobile-filter-button" data-job-filter-drawer aria-label="Open job filters"><x-vuero.icon name="settings" size="20" /><span>Filters</span></button>
</div>
