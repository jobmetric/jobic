<x-vuero.layouts.app title="Promotion Page" :script="$layoutScript">
    <section class="layout-page promotion-page-wrapper" data-utility-promotion aria-label="Promotion Page">
        <div class="wrapper-outer">
            <div class="wrapper-inner">
                <div class="action-box">
                    <div class="box-content">
                        <img class="light-image-block is-larger" src="/vuero/images/illustrations/placeholders/promotion.svg" alt="">
                        <img class="dark-image-block is-larger" src="/vuero/images/illustrations/placeholders/promotion-dark.svg" alt="">
                        <h3>Empower your team to do greater things collaboratively</h3>
                        <div class="price">
                            <span>99</span>
                            <span>Per Month</span>
                        </div>
                        <div class="buttons">
                            <button type="button" class="v-button is-dark-outlined" data-promotion-skip>Skip</button>
                            <button type="button" class="v-button is-primary is-raised" data-promotion-upgrade>Upgrade</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="side-wrapper">
                <div class="side-inner">
                    <div class="side-title">
                        <h3>Features to make it better</h3>
                        <p>Take a look at some incredible features</p>
                    </div>

                    <div class="action-list">
                        <x-vuero.layouts.media-block
                            title="Includes 8 team seats"
                            text="Each team member on your account can create projects and tasks."
                        />
                        <x-vuero.layouts.media-block
                            title="Integrations"
                            text="Add up to 5 addons to your main application to help you manage."
                        />
                        <x-vuero.layouts.media-block
                            title="Scale"
                            text="Your application grows in parallel of your business, without any effort."
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
