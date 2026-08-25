<section class="lifestyle-profile">
    <div class="lifestyle-profile__avatar">
        <img src="{{ asset('vuero/images/admin/business/avatars/15.svg') }}" alt="Clarissa Geller">
        <button type="button" data-influencer-follow aria-label="Follow Clarissa" aria-pressed="false"><x-vuero.icon name="plus" size="17" /></button>
    </div>
    <div class="lifestyle-profile__meta">
        <div class="lifestyle-profile__identity">
            <div><h1>sweet_candy13 <x-vuero.icon name="star" size="18" /></h1><p>Clarissa Geller</p></div>
            <div class="lifestyle-tags"><span class="is-info">Music</span><span>Lifestyle</span></div>
        </div>
        <div class="lifestyle-profile__counts">
            <span><strong>1247</strong> Posts</span>
            <span><strong>38.3K</strong> Followers</span>
            <span><strong>329</strong> Following</span>
        </div>
        <p class="lifestyle-profile__bio">Artist, musician, songwriter, influencer, these are the many names people give me. But for you I am simply Clarissa. *Forever with all my friends* // Latest video can be found here youtu.be/8Tcee5Cyz</p>
        <div class="lifestyle-achievements" aria-label="Clarissa's achievements">
            <button type="button" class="is-danger" data-achievement="On Fire" data-achievement-copy="Lorem ipsum dolor sit amet, consectetur adipiscing elit." aria-label="On Fire achievement"><x-vuero.icon name="flame" size="17" /></button>
            <button type="button" class="is-primary" data-achievement="Post Veteran" data-achievement-copy="Lorem ipsum dolor sit amet, consectetur adipiscing elit." aria-label="Post Veteran achievement"><x-vuero.icon name="award" size="17" /></button>
            <button type="button" class="is-yellow" data-achievement="Social Champion" data-achievement-copy="Lorem ipsum dolor sit amet, consectetur adipiscing elit." aria-label="Social Champion achievement"><x-vuero.icon name="star" size="17" /></button>
        </div>
    </div>
    <button type="button" class="lifestyle-more" data-influencer-menu aria-label="Profile options" aria-expanded="false"><x-vuero.icon name="more" size="20" /></button>
</section>
