import { pageData } from '../../common/page';
import { iconMarkup } from '../../common/icons';
const fmText = key => pageData().fileManager?.texts?.[key] ?? '';
export default {
    renderVideoPreview(item) {
        const stage = this.elements.previewStage;
        stage.className = 'panel-file-manager__preview-stage is-video';
        const extension = this.escape((item.extension || 'VIDEO').toUpperCase());
        stage.innerHTML = `<div class="panel-file-manager__video-player" data-video-player tabindex="0">
            <section class="panel-file-manager__video-cinema">
                <div class="panel-file-manager__video-ambient" aria-hidden="true"></div>
                <video data-video-element src="${this.escape(item.url)}" preload="metadata" playsinline crossorigin="anonymous"></video>
                <div class="panel-file-manager__video-frame-glow" aria-hidden="true"></div>
                <div class="panel-file-manager__video-topline">
                    <div class="panel-file-manager__video-title"><span><i></i> ${fmText(93)}</span><strong dir="auto">${this.escape(item.name)}</strong></div>
                    <div class="panel-file-manager__video-badges"><b>${extension}</b><b data-video-resolution>${fmText(94)}</b><b data-video-speed-label>${fmText(95)}×</b></div>
                </div>
                <div class="panel-file-manager__video-loader hidden" data-video-loader><span></span><small>${fmText(96)}</small></div>
                <button type="button" class="panel-file-manager__video-center-play" data-video-center-play aria-label="${fmText(97)}">${this.videoSvg('play')}</button>
            </section>
            <section class="panel-file-manager__video-controls" data-video-controls>
                <div class="panel-file-manager__video-timeline">
                    <span data-video-current>${fmText(98)}</span>
                    <label><span class="panel-file-manager__video-buffer" data-video-buffer></span><input type="range" min="0" max="100" value="0" step="0.01" data-video-seek aria-label="${fmText(99)}"></label>
                    <span data-video-duration>${fmText(98)}</span>
                </div>
                <div class="panel-file-manager__video-commandbar">
                    <div class="panel-file-manager__video-actions is-leading">
                        <button type="button" data-video-loop aria-pressed="false" aria-label="${fmText(100)}" title="${fmText(101)}">${this.videoSvg('repeat')}<span>${fmText(100)}</span></button>
                        <button type="button" data-video-back aria-label="${fmText(102)}" title="${fmText(102)}">${this.videoSvg('back')}<span>${fmText(103)}</span></button>
                        <button type="button" data-video-snapshot aria-label="${fmText(104)}" title="${fmText(105)}">${this.videoSvg('camera')}<span>${fmText(106)}</span></button>
                    </div>
                    <button type="button" class="panel-file-manager__video-main-play" data-video-play aria-label="${fmText(107)}" title="${fmText(108)}">${this.videoSvg('play')}</button>
                    <div class="panel-file-manager__video-actions is-trailing">
                        <button type="button" data-video-forward aria-label="${fmText(109)}" title="${fmText(109)}">${this.videoSvg('forward')}<span>${fmText(110)}+</span></button>
                        <button type="button" data-video-fit aria-pressed="false" aria-label="${fmText(111)}" title="${fmText(112)}">${this.videoSvg('fit')}<span>${fmText(113)}</span></button>
                        <button type="button" data-video-pip aria-label="${fmText(114)}" title="${fmText(114)}">${this.videoSvg('pip')}<span>${fmText(114)}</span></button>
                        <button type="button" data-video-fullscreen aria-label="${fmText(115)}" title="${fmText(115)}">${this.videoSvg('fullscreen')}<span>${fmText(115)}</span></button>
                    </div>
                </div>
                <div class="panel-file-manager__video-utilities">
                    <div class="panel-file-manager__video-volume"><button type="button" data-video-mute aria-pressed="false" aria-label="${fmText(116)}" title="${fmText(117)}">${this.videoSvg('volume')}<span>${fmText(118)}</span></button><input type="range" min="0" max="1" value="0.85" step="0.01" data-video-volume aria-label="${fmText(119)}"><output data-video-volume-value>${fmText(120)}</output></div>
                    <div class="panel-file-manager__video-playback-settings">
                        <span>${this.videoSvg('speed')}<b>${fmText(121)}</b><small>${fmText(122)}</small></span>
                        <div class="panel-file-manager__video-speed">
                            <button type="button" data-video-speed-trigger aria-expanded="false" aria-haspopup="listbox"><b dir="ltr" data-video-speed-value>1×</b>${this.videoSvg('chevron')}</button>
                            <select class="hidden" data-video-speed tabindex="-1" aria-hidden="true"><option value="0.5">${fmText(123)}×</option><option value="0.75">${fmText(124)}×</option><option value="1" selected>${fmText(95)}×</option><option value="1.25">${fmText(125)}×</option><option value="1.5">${fmText(126)}×</option><option value="2">${fmText(127)}×</option></select>
                            <div class="panel-file-manager__video-speed-menu hidden" data-video-speed-menu role="listbox" aria-label="${fmText(128)}">
                                <button type="button" data-video-speed-option="0.5"><span><b dir="ltr">0.5×</b><small>${fmText(129)}</small></span><i></i></button><button type="button" data-video-speed-option="0.75"><span><b dir="ltr">0.75×</b><small>${fmText(130)}</small></span><i></i></button><button type="button" class="is-active" data-video-speed-option="1" aria-selected="true"><span><b dir="ltr">1×</b><small>${fmText(131)}</small></span><i></i></button><button type="button" data-video-speed-option="1.25"><span><b dir="ltr">1.25×</b><small>${fmText(132)}</small></span><i></i></button><button type="button" data-video-speed-option="1.5"><span><b dir="ltr">1.5×</b><small>${fmText(133)}</small></span><i></i></button><button type="button" data-video-speed-option="2"><span><b dir="ltr">2×</b><small>${fmText(134)}</small></span><i></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>`;

        const player = stage.querySelector('[data-video-player]');
        const video = player.querySelector('[data-video-element]');
        const state = { item, player, video, scrubbing: false, controlTimer: null, fullscreenHandler: null };
        this.videoPreviewState = state;
        video.volume = .85;
        this.bindVideoPlayer(state);
        video.play().catch(() => this.updateVideoPlaybackState(state));
    },

    videoSvg(name) {
        const icons = {
            play: 'play', pause: 'pause', back: 'rewind-10', forward: 'forward-10',
            volume: 'volume', muted: 'volume-off', repeat: 'repeat', fit: 'maximize',
            camera: 'camera', pip: 'layout', fullscreen: 'maximize', fullscreenExit: 'minimize',
            speed: 'gauge', chevron: 'chevron-down',
        };
        return iconMarkup(icons[name] || 'play', 20);
    },

    bindVideoPlayer(state) {
        const { player, video } = state;
        const play = player.querySelector('[data-video-play]');
        const centerPlay = player.querySelector('[data-video-center-play]');
        const seek = player.querySelector('[data-video-seek]');
        const volume = player.querySelector('[data-video-volume]');
        const mute = player.querySelector('[data-video-mute]');
        const loop = player.querySelector('[data-video-loop]');
        const speed = player.querySelector('[data-video-speed]');
        const speedTrigger = player.querySelector('[data-video-speed-trigger]');
        const speedMenu = player.querySelector('[data-video-speed-menu]');
        const pip = player.querySelector('[data-video-pip]');
        const fullscreen = player.querySelector('[data-video-fullscreen]');
        const toggle = () => this.toggleVideoPlayback(state);
        play.addEventListener('click', toggle);
        centerPlay.addEventListener('click', toggle);
        video.addEventListener('click', toggle);
        video.addEventListener('dblclick', () => this.toggleVideoFullscreen(state));
        player.querySelector('[data-video-back]').addEventListener('click', () => { video.currentTime = Math.max(0, video.currentTime - 10); });
        player.querySelector('[data-video-forward]').addEventListener('click', () => { video.currentTime = Math.min(video.duration || Infinity, video.currentTime + 10); });
        seek.addEventListener('pointerdown', () => { state.scrubbing = true; });
        seek.addEventListener('input', () => {
            if (Number.isFinite(video.duration)) video.currentTime = (Number(seek.value) / 100) * video.duration;
            this.updateVideoTimeline(state);
        });
        seek.addEventListener('change', () => { state.scrubbing = false; });
        volume.addEventListener('input', () => { video.volume = Number(volume.value); video.muted = false; this.updateVideoVolume(state); });
        mute.addEventListener('click', () => { video.muted = !video.muted; this.updateVideoVolume(state); });
        loop.addEventListener('click', () => {
            video.loop = !video.loop;
            loop.classList.toggle('is-active', video.loop);
            loop.setAttribute('aria-pressed', video.loop ? 'true' : 'false');
        });
        const updateSpeed = () => {
            video.playbackRate = Number(speed.value);
            player.querySelector('[data-video-speed-label]').textContent = `${speed.value}×`;
            player.querySelector('[data-video-speed-value]').textContent = `${Number(speed.value).toLocaleString('en-US', { maximumFractionDigits: 2 })}×`;
            player.querySelectorAll('[data-video-speed-option]').forEach((option) => {
                const active = option.dataset.videoSpeedOption === speed.value;
                option.classList.toggle('is-active', active);
                option.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        };
        speed.addEventListener('change', updateSpeed);
        speedTrigger.addEventListener('click', () => {
            const expanded = speedTrigger.getAttribute('aria-expanded') === 'true';
            speedTrigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            speedMenu.classList.toggle('hidden', expanded);
        });
        player.querySelectorAll('[data-video-speed-option]').forEach((option) => option.addEventListener('click', () => {
            speed.value = option.dataset.videoSpeedOption;
            updateSpeed();
            speedMenu.classList.add('hidden');
            speedTrigger.setAttribute('aria-expanded', 'false');
            speedTrigger.focus();
        }));
        player.addEventListener('click', (event) => {
            if (event.target.closest('.panel-file-manager__video-speed')) return;
            speedMenu.classList.add('hidden');
            speedTrigger.setAttribute('aria-expanded', 'false');
        });
        player.querySelector('[data-video-fit]').addEventListener('click', (event) => {
            const cover = player.classList.toggle('is-cover');
            event.currentTarget.classList.toggle('is-active', cover);
            event.currentTarget.setAttribute('aria-pressed', cover ? 'true' : 'false');
            event.currentTarget.title = cover ? `${fmText(135)}` : `${fmText(111)}`;
        });
        player.querySelector('[data-video-snapshot]').addEventListener('click', () => this.captureVideoFrame(state));
        pip.disabled = !document.pictureInPictureEnabled || typeof video.requestPictureInPicture !== 'function';
        pip.addEventListener('click', async () => {
            try {
                if (document.pictureInPictureElement === video) await document.exitPictureInPicture();
                else await video.requestPictureInPicture();
            } catch { this.toast(`${fmText(136)}`, 'warning'); }
        });
        fullscreen.addEventListener('click', () => this.toggleVideoFullscreen(state));
        state.fullscreenHandler = () => {
            const active = document.fullscreenElement === player;
            fullscreen.innerHTML = `${this.videoSvg(active ? 'fullscreenExit' : 'fullscreen')}<span>${active ? `${fmText(137)}` : `${fmText(115)}`}</span>`;
            fullscreen.classList.toggle('is-active', active);
        };
        document.addEventListener('fullscreenchange', state.fullscreenHandler);
        video.addEventListener('loadedmetadata', () => {
            player.querySelector('[data-video-resolution]').textContent = `${video.videoWidth.toLocaleString(document.documentElement.lang || 'fa')}×${video.videoHeight.toLocaleString(document.documentElement.lang || 'fa')}`;
            this.updateVideoTimeline(state);
        });
        ['durationchange', 'timeupdate', 'progress'].forEach((eventName) => video.addEventListener(eventName, () => this.updateVideoTimeline(state)));
        ['play', 'pause', 'ended'].forEach((eventName) => video.addEventListener(eventName, () => this.updateVideoPlaybackState(state)));
        ['waiting', 'stalled'].forEach((eventName) => video.addEventListener(eventName, () => player.querySelector('[data-video-loader]').classList.remove('hidden')));
        ['playing', 'canplay'].forEach((eventName) => video.addEventListener(eventName, () => player.querySelector('[data-video-loader]').classList.add('hidden')));
        video.addEventListener('error', () => {
            player.querySelector('[data-video-loader]').classList.add('hidden');
            this.toast(`${fmText(138)}`, 'error');
        });
        player.addEventListener('pointermove', () => this.showVideoControls(state));
        player.addEventListener('pointerleave', () => { if (!video.paused) this.scheduleVideoControls(state, 600); });
        player.addEventListener('keydown', (event) => this.handleVideoKeyboard(state, event));
        this.updateVideoPlaybackState(state);
        this.updateVideoVolume(state);
    },

    async toggleVideoPlayback(state) {
        if (!state?.video) return;
        if (state.video.paused) {
            try { await state.video.play(); }
            catch { this.toast(`${fmText(139)}`, 'warning'); }
        } else state.video.pause();
    },

    updateVideoPlaybackState(state) {
        if (!state?.player) return;
        const playing = !state.video.paused && !state.video.ended;
        state.player.classList.toggle('is-playing', playing);
        state.player.querySelector('[data-video-play]').innerHTML = this.videoSvg(playing ? 'pause' : 'play');
        state.player.querySelector('[data-video-play]').setAttribute('aria-label', playing ? `${fmText(140)}` : `${fmText(107)}`);
        state.player.querySelector('[data-video-center-play]').innerHTML = this.videoSvg(playing ? 'pause' : 'play');
        if (playing) this.scheduleVideoControls(state);
        else this.showVideoControls(state, false);
    },

    updateVideoTimeline(state) {
        const { player, video } = state;
        const duration = Number.isFinite(video.duration) ? video.duration : 0;
        const progress = duration ? (video.currentTime / duration) * 100 : 0;
        const seek = player.querySelector('[data-video-seek]');
        if (!state.scrubbing) seek.value = String(progress);
        seek.style.setProperty('--video-played', `${progress}%`);
        player.querySelector('[data-video-current]').textContent = this.formatAudioTime(video.currentTime || 0);
        player.querySelector('[data-video-duration]').textContent = this.formatAudioTime(duration);
        let buffered = 0;
        if (duration && video.buffered?.length) buffered = (video.buffered.end(video.buffered.length - 1) / duration) * 100;
        player.querySelector('[data-video-buffer]').style.width = `${Math.min(100, buffered)}%`;
    },

    updateVideoVolume(state) {
        const { player, video } = state;
        player.querySelector('[data-video-volume]').value = String(video.volume);
        const mute = player.querySelector('[data-video-mute]');
        const muted = video.muted || video.volume === 0;
        mute.innerHTML = `${this.videoSvg(muted ? 'muted' : 'volume')}<span>${muted ? `${fmText(141)}` : `${fmText(118)}`}</span>`;
        mute.classList.toggle('is-active', video.muted || video.volume === 0);
        mute.setAttribute('aria-pressed', video.muted ? 'true' : 'false');
        mute.setAttribute('aria-label', muted ? `${fmText(142)}` : `${fmText(116)}`);
        const output = player.querySelector('[data-video-volume-value]');
        if (output) output.textContent = muted ? `${fmText(143)}` : `${Math.round(video.volume * 100).toLocaleString(document.documentElement.lang || 'fa')}${fmText(92)}`;
    },

    scheduleVideoControls(state, delay = 2200) {
        clearTimeout(state.controlTimer);
        state.controlTimer = setTimeout(() => {
            if (!state.video.paused && this.videoPreviewState === state) state.player.classList.add('is-controls-hidden');
        }, delay);
    },

    showVideoControls(state, schedule = true) {
        clearTimeout(state.controlTimer);
        state.player.classList.remove('is-controls-hidden');
        if (schedule && !state.video.paused) this.scheduleVideoControls(state);
    },

    async toggleVideoFullscreen(state) {
        try {
            if (document.fullscreenElement === state.player) await document.exitFullscreen();
            else await state.player.requestFullscreen();
        } catch { this.toast(`${fmText(144)}`, 'warning'); }
    },

    captureVideoFrame(state) {
        const { video, item } = state;
        if (!video.videoWidth || !video.videoHeight) {
            this.toast(`${fmText(145)}`, 'warning');
            return;
        }
        try {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
            canvas.toBlob((blob) => {
                if (!blob) return this.toast(`${fmText(146)}`, 'error');
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = `${String(item.name).replace(/\.[^.]+$/, '')}-frame-${Math.floor(video.currentTime * 1000)}.png`;
                link.click();
                setTimeout(() => URL.revokeObjectURL(link.href), 1000);
                this.toast(`${fmText(147)}`);
            }, 'image/png');
        } catch { this.toast(`${fmText(148)}`, 'error'); }
    },

    handleVideoKeyboard(state, event) {
        if (['INPUT', 'SELECT'].includes(event.target.tagName)) return;
        const key = event.key.toLowerCase();
        if (![' ', 'k', 'arrowleft', 'arrowright', 'arrowup', 'arrowdown', 'm', 'f', 'p', 'r', 's', ',', '.'].includes(key)) return;
        event.preventDefault();
        event.stopPropagation();
        const { video, player } = state;
        if (key === ' ' || key === 'k') this.toggleVideoPlayback(state);
        if (key === 'arrowleft') video.currentTime = Math.max(0, video.currentTime - 5);
        if (key === 'arrowright') video.currentTime = Math.min(video.duration || Infinity, video.currentTime + 5);
        if (key === 'arrowup' || key === 'arrowdown') { video.muted = false; video.volume = Math.max(0, Math.min(1, video.volume + (key === 'arrowup' ? .05 : -.05))); this.updateVideoVolume(state); }
        if (key === 'm') { video.muted = !video.muted; this.updateVideoVolume(state); }
        if (key === 'f') this.toggleVideoFullscreen(state);
        if (key === 'p') player.querySelector('[data-video-pip]:not(:disabled)')?.click();
        if (key === 'r') player.querySelector('[data-video-loop]').click();
        if (key === 's') this.captureVideoFrame(state);
        if (key === ',' || key === '.') { video.pause(); video.currentTime = Math.max(0, Math.min(video.duration || Infinity, video.currentTime + (key === '.' ? 1 / 30 : -1 / 30))); }
        this.showVideoControls(state);
    },

    destroyVideoPreview() {
        const state = this.videoPreviewState;
        if (!state) return;
        clearTimeout(state.controlTimer);
        try { state.video.pause(); } catch {}
        if (state.fullscreenHandler) document.removeEventListener('fullscreenchange', state.fullscreenHandler);
        if (document.pictureInPictureElement === state.video) document.exitPictureInPicture?.().catch(() => {});
        if (document.fullscreenElement === state.player) document.exitFullscreen?.().catch(() => {});
        this.videoPreviewState = null;
    },

};
