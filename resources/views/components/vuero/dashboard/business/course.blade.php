@php
    $courses = [
        ['category' => 'Anatomy', 'tone' => 'green', 'title' => 'ANA118 - Anatomy and physiology of young subjects', 'summary' => 'This week features a deeper approach of young subject anatomy and basic physiological properties. It also focuses on the latest released articles.', 'avatars' => [21, 33, 25], 'participants' => 49, 'duration' => '45min', 'comments' => 25, 'action' => 'Week 6 assessment tasks', 'date' => 'Feb 3, 2021'],
        ['category' => 'Immunology', 'tone' => 'purple', 'title' => 'IMM107 - Immunology and treatment of human disease', 'summary' => 'This unit has a strong focus on significant clinical problems in immunology and the scientific background to these problems.', 'avatars' => [19, 27, 5], 'participants' => 26, 'duration' => '95min', 'comments' => 0, 'action' => 'Written Examination', 'date' => '9:00-12:00 Feb 8, 2021'],
        ['category' => 'Cardiology', 'tone' => 'danger', 'title' => 'CAR112 - Cardiovascular Metabolic Management', 'summary' => 'This unit has a strong focus on significant clinical problems in cardiology and the scientific background to these problems.', 'avatars' => [36, 15, 18], 'participants' => 54, 'duration' => '95min', 'comments' => 0, 'action' => 'Written Examination', 'date' => '9:00-12:00 Feb 8, 2021'],
    ];
    $files = [
        ['icon' => 'pdf.svg', 'name' => 'Holtz - Complete Anatomy', 'size' => '94.7 MB', 'uploaded' => '2 days ago'],
        ['icon' => 'sheet.svg', 'name' => 'Assessing doses Pt. 2', 'size' => '79 KB', 'uploaded' => '1 week ago'],
        ['icon' => 'doc-2.svg', 'name' => 'Written exam template', 'size' => '42 KB', 'uploaded' => '2 weeks ago'],
    ];
    $students = [
        ['avatar' => 'vuero', 'name' => 'Erik K.', 'progress' => 76, 'location' => 'Las Vegas, NV', 'status' => 'Online', 'attendance' => 'On Time'],
        ['avatar' => '22', 'name' => 'Jimmy H.', 'progress' => 79, 'location' => 'Los Angeles, CA', 'status' => 'Offline', 'attendance' => 'Late (10min)'],
        ['avatar' => '25', 'name' => 'Melany W.', 'progress' => 58, 'location' => 'San Jose, CA', 'status' => 'Online', 'attendance' => 'On Time'],
        ['avatar' => '12', 'name' => 'Joshua S.', 'progress' => 66, 'location' => 'Las Vegas, NV', 'status' => 'Online', 'attendance' => 'On Time'],
        ['avatar' => '13', 'name' => 'Tara S.', 'progress' => 87, 'location' => 'Los Angeles, CA', 'status' => 'Busy', 'attendance' => 'On Time'],
    ];
@endphp

<div class="business-dashboard course-dashboard" data-business-dashboard="course">
    <header class="course-title-row">
        <div><h1>Week 6 Classes</h1><p>Classes from January 6 to 10</p></div>
        <button type="button" class="vuero-button is-primary" data-course-schedule>Open Schedule</button>
    </header>

    <section class="course-card-grid" aria-label="Week 6 classes">
        @foreach ($courses as $course)
            <article class="dashboard-card course-card" data-course-card data-course="{{ $course['category'] }}">
                <span class="business-tag is-{{ $course['tone'] }}">{{ $course['category'] }}</span>
                <h2>{{ $course['title'] }}</h2>
                <p class="course-card__summary">{{ $course['summary'] }}</p>
                <div class="course-participants">
                    <div class="business-avatar-stack">
                        @foreach ($course['avatars'] as $avatar)
                            <img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt="">
                        @endforeach
                        <span>+{{ $course['participants'] - 3 }}</span>
                    </div>
                    <button type="button" class="business-action-link" data-course-discussion>Class discussion</button>
                </div>
                <button type="button" class="course-attachment" data-course-recording>
                    <strong>Week 6 lecture recording</strong>
                    <span><x-vuero.icon name="clock" size="15" /> {{ $course['duration'] }}</span>
                    <span><x-vuero.icon name="message" size="15" /> {{ $course['comments'] }}</span>
                </button>
                <button type="button" class="course-attachment" data-course-assessment>
                    <strong>{{ $course['action'] }}</strong>
                    <span><x-vuero.icon name="calendar" size="15" /> {{ $course['date'] }}</span>
                </button>
                <button type="button" class="vuero-button is-primary course-card__action" data-course-open>View Class</button>
            </article>
        @endforeach
    </section>

    <section class="course-lower-grid">
        <div class="course-files-column">
            <header class="course-section-title"><div><h2>Week 6 Files</h2><p>Files you will need for this week</p></div></header>
            <div class="course-file-list">
                @foreach ($files as $file)
                    <article class="dashboard-card course-file" data-course-file>
                        <img src="{{ asset('vuero/images/admin/business/files/'.$file['icon']) }}" alt="">
                        <div><h3>{{ $file['name'] }}</h3><p>{{ $file['size'] }} <i></i> Uploaded {{ $file['uploaded'] }}</p></div>
                        <div class="business-dropdown course-file__dropdown" data-business-dropdown>
                            <button type="button" class="business-more-button" data-business-dropdown-trigger aria-haspopup="menu" aria-expanded="false" aria-label="File actions"><x-vuero.icon name="more" size="18" /></button>
                            <div class="business-dropdown__menu business-dropdown__menu--media" role="menu">
                                <button type="button" role="menuitem" data-file-action="download"><x-vuero.icon name="download" size="18" /><span><strong>Download</strong><small>Download this file</small></span></button>
                                <button type="button" role="menuitem" data-file-action="upload"><x-vuero.icon name="upload" size="18" /><span><strong>Upload</strong><small>Upload a new version</small></span></button>
                                <hr>
                                <button type="button" role="menuitem" data-file-action="permissions"><x-vuero.icon name="lock" size="18" /><span><strong>Permissions</strong><small>Manage file permissions</small></span></button>
                                <button type="button" role="menuitem" data-file-action="share"><x-vuero.icon name="share" size="18" /><span><strong>Share</strong><small>Share this file</small></span></button>
                                <hr>
                                <button type="button" role="menuitem" class="is-danger" data-file-action="delete"><x-vuero.icon name="trash" size="18" /><span><strong>Delete</strong><small>Delete this file</small></span></button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="course-students-column">
            <header class="course-section-title course-students-title">
                <div><h2>Students</h2><p>Summary of attending students</p></div>
                <label class="business-search-input"><x-vuero.icon name="search" size="17" /><input type="search" placeholder="Search students..." data-student-search></label>
            </header>
            <div class="course-student-table" role="table" aria-label="Attending students">
                @foreach ($students as $student)
                    <article class="course-student" role="row" data-student data-search="{{ strtolower($student['name'].' '.$student['location'].' '.$student['status']) }}">
                        @if ($student['avatar'] === 'vuero')
                            <img src="{{ asset('vuero/images/admin/avatars/vuero-1.svg') }}" alt="">
                        @else
                            <img src="{{ asset('vuero/images/admin/business/avatars/'.$student['avatar'].'.svg') }}" alt="">
                        @endif
                        <div class="course-student__identity"><strong>{{ $student['name'] }}</strong><span>{{ $student['progress'] }}% completed</span></div>
                        <span class="course-student__location">{{ $student['location'] }}</span>
                        <span><i class="business-status is-{{ strtolower($student['status']) }}">{{ $student['status'] }}</i></span>
                        <span class="course-student__attendance">{{ $student['attendance'] }}</span>
                        <button type="button" class="vuero-button course-student__chat" data-student-chat>Chat</button>
                    </article>
                @endforeach
                <p class="business-empty-state" data-student-empty hidden>No students match your search.</p>
            </div>
        </div>
    </section>
</div>
