@php
    $photo = fn (int $number): string => '/vuero/images/layouts/grid-cards/posts/'.$number.'.jpg';
    $avatar = fn (string $file): string => str_contains($file, 'vuero-1.svg')
        ? '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg'
        : '/vuero/images/layouts/list-view-v1/avatars/'.$file;
@endphp

<x-vuero.layouts.app title="Grid Tiles 3" :script="$layoutScript">
    <section class="tile-grid-page tile-grid tile-grid-v3" data-grid-tile-v3 aria-label="Grid Tiles 3">
        <x-vuero.layouts.tile-grid-toolbar
            :filter-options="['All Posts', 'Recent Posts', 'Older Posts', 'Popular Posts']"
            filter-selected="All Posts"
            cta-label="Add User"
        />

        <x-vuero.layouts.list-placeholder
            name="search"
            :large="true"
            title="We couldn't find any matching results."
            subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
            image="/vuero/images/layouts/grid-tiles/placeholders/search-6.svg"
            dark-image="/vuero/images/layouts/grid-tiles/placeholders/search-6-dark.svg"
        />

        <div data-tile-items>
            <div class="tile is-ancestor">
                <div class="tile is-parent">
                    <x-vuero.layouts.tile-grid-v3-item size="medium" title="How to serve coffee at the office the proper way" :image="$photo(11)" author-name="Alejandro B." :author-avatar="$avatar('39.jpg')" published="30 minutes ago" />
                </div>
                <div class="tile is-parent">
                    <x-vuero.layouts.tile-grid-v3-item size="medium" title="Teamwork can dramatically increase productivity" :image="$photo(12)" author-name="Hilde V." :author-avatar="$avatar('15.jpg')" published="4 hours ago" />
                </div>
                <div class="tile is-parent">
                    <x-vuero.layouts.tile-grid-v3-item size="medium" title="Is team building a scam or the next thing?" :image="$photo(13)" author-name="Sandrine C." :author-avatar="$avatar('24.jpg')" published="5 hours ago" />
                </div>
            </div>

            <div class="tile is-ancestor">
                <div class="tile is-vertical is-8">
                    <div class="tile">
                        <div class="tile is-parent is-vertical">
                            <x-vuero.layouts.tile-grid-v3-item size="medium" title="3 things you should know when applying for developer jobs" :image="$photo(14)" author-name="Carmen E." :author-avatar="$avatar('27.jpg')" published="8 hours ago" />
                            <x-vuero.layouts.tile-grid-v3-item size="medium" title="The construction business massively invests in mobile apps" :image="$photo(15)" author-name="Henry G." :author-avatar="$avatar('10.jpg')" published="1 day ago" />
                        </div>
                        <div class="tile is-parent">
                            <x-vuero.layouts.tile-grid-v3-item size="large" title="Finding the right spot for your startup" :image="$photo(16)" author-name="Jimmy H." :author-avatar="$avatar('22.jpg')" published="1 day ago" />
                        </div>
                    </div>
                    <div class="tile is-parent">
                        <x-vuero.layouts.tile-grid-v3-item
                            size="wide"
                            title="4 tips to make your business lunches awesome"
                            :image="$photo(18)"
                            author-name="Jason G."
                            :author-avatar="$avatar('16.jpg')"
                            published="2 days ago"
                            :paragraphs="['Lorem ipsum dolor sit amet, consectetur adipiscing elit. Optime, inquam. Duo Reges constructio.']"
                            extra-paragraph-class="h-hidden-mobile h-hidden-tablet-p"
                        />
                    </div>
                </div>
                <div class="tile is-parent">
                    <x-vuero.layouts.tile-grid-v3-item
                        size="tall"
                        title="Setting up a design system for your app project"
                        :image="$photo(19)"
                        author-name="Courtney W."
                        :author-avatar="$avatar('26.jpg')"
                        published="2 days ago"
                        :paragraphs="['Lorem ipsum dolor sit amet, consectetur adipiscing elit. Optime, inquam. Duo Reges constructio.']"
                        extra-paragraph-class="h-hidden-mobile h-hidden-tablet-p"
                    />
                </div>
            </div>

            <div class="tile is-ancestor">
                <div class="tile is-parent">
                    <x-vuero.layouts.tile-grid-v3-item size="wide" title="Foundation of a great E-Learning system" :image="$photo(20)" author-name="Erik K." :author-avatar="$avatar('vuero-1.svg')" published="2 days ago" />
                </div>
                <div class="tile is-parent is-6">
                    <x-vuero.layouts.tile-grid-v3-item size="wide" title="Why it pays to profile your customers" :image="$photo(23)" author-name="Jeanne M." :author-avatar="$avatar('40.jpg')" published="3 days ago" />
                </div>
            </div>

            <div class="tile is-ancestor">
                <div class="tile is-parent">
                    <x-vuero.layouts.tile-grid-v3-item size="medium" title="Using flashy colors in your websites and apps" :image="$photo(22)" author-name="Ana B." :author-avatar="$avatar('9.jpg')" published="3 days ago" />
                </div>
                <div class="tile is-parent">
                    <x-vuero.layouts.tile-grid-v3-item size="medium" title="Building a consistent and talented team" :image="$photo(24)" author-name="Greta K." :author-avatar="$avatar('19.jpg')" published="3 days ago" />
                </div>
                <div class="tile is-parent">
                    <x-vuero.layouts.tile-grid-v3-item size="medium" title="Diving into building an e-commerce brand - part 1" :image="$photo(27)" author-name="Harvey M." :author-avatar="$avatar('33.jpg')" published="4 days ago" />
                </div>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
