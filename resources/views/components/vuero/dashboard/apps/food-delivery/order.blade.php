<aside class="food-delivery-order" aria-label="Your food order">
    <nav class="food-delivery-order-tools" aria-label="Food delivery shortcuts">
        <button type="button" class="is-active" data-food-tool="cart" aria-label="Current order" aria-pressed="true">
            <x-vuero.icon name="box" size="18" />
            <span data-food-tool-count>0</span>
        </button>
        <button type="button" data-food-tool="offers" aria-label="Delivery offers" aria-pressed="false"><x-vuero.icon name="ticket" size="18" /></button>
        <button type="button" data-food-tool="time" aria-label="Delivery time" aria-pressed="false"><x-vuero.icon name="clock" size="18" /></button>
        <button type="button" data-food-tool="settings" aria-label="Delivery settings" aria-pressed="false"><x-vuero.icon name="settings" size="18" /></button>
    </nav>

    <section class="food-delivery-order-card">
        <header>
            <div><span>Your basket</span><h2>My Order</h2></div>
            <button type="button" data-food-clear aria-label="Clear order" hidden>Clear</button>
        </header>

        <button type="button" class="food-delivery-address" data-food-address>
            <span><x-vuero.icon name="location" size="18" /></span>
            <span><strong data-food-address-label>625 St Marks Ave</strong><small><x-vuero.icon name="clock" size="12" /> <b data-food-order-time>35 min</b></small></span>
            <em>Edit</em>
        </button>

        <div class="food-delivery-order-list" data-food-order-list></div>

        <div class="food-delivery-order-empty" data-food-order-empty>
            <div aria-hidden="true">
                <span></span><span></span><span></span>
                <x-vuero.icon name="box" size="43" />
            </div>
            <h3>No items yet</h3>
            <p>Choose something delicious and it will appear here.</p>
        </div>

        <footer>
            <div class="food-delivery-total">
                <span>Total</span>
                <strong data-food-total>$0.00</strong>
            </div>
            <div class="food-delivery-checkout-row">
                <div class="food-delivery-persons">
                    <span>Persons</span>
                    <div>
                        <button type="button" data-food-person-minus aria-label="Remove a person" disabled>−</button>
                        <strong data-food-persons>1</strong>
                        <button type="button" data-food-person-plus aria-label="Add a person">+</button>
                    </div>
                </div>
                <button type="button" class="food-delivery-checkout" data-food-checkout disabled>
                    Checkout
                    <x-vuero.icon name="arrow-right" size="16" />
                </button>
            </div>
        </footer>
    </section>
</aside>
