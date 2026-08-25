<aside class="inbox-compose" data-inbox-compose aria-hidden="true" aria-label="Compose a new message">
    <header class="inbox-header">
        <h2>New Message</h2>
        <button type="button" class="inbox-action" data-inbox-compose-close aria-label="Close composer"><x-vuero.icon name="x" size="19" /></button>
    </header>
    <form data-inbox-compose-form>
        <label><span>To</span><input type="email" placeholder="recipient@vuero.io" required data-inbox-compose-to></label>
        <label><span>Subject</span><input type="text" placeholder="Message subject" required data-inbox-compose-subject></label>
        <label class="inbox-compose__body"><span>Message</span><textarea rows="12" placeholder="Write your message..." required data-inbox-compose-body></textarea>
            <button type="button" class="inbox-compose__attach" data-inbox-compose-attach aria-label="Attach a file"><x-vuero.icon name="upload" size="17" /></button>
            <button type="submit" class="inbox-compose__send">Send Message</button>
        </label>
        <p data-inbox-compose-file hidden></p>
    </form>
</aside>
