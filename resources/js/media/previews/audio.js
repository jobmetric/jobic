import { pageData } from '../../common/page';
import { iconMarkup } from '../../common/icons';
const fmText = key => pageData().fileManager?.texts?.[key] ?? '';
export default {
    renderAudioPreview(item) {
        const stage = this.elements.previewStage;
        stage.className = 'panel-file-manager__preview-stage is-audio';
        const extension = this.escape((item.extension || 'AUDIO').toUpperCase());
        stage.innerHTML = `<div class="panel-file-manager__audio-player" data-audio-player tabindex="0">
            <audio data-audio-element src="${this.escape(item.url)}" preload="metadata" crossorigin="anonymous"></audio>
            <section class="panel-file-manager__audio-visual">
                <canvas data-audio-visualizer aria-label="${fmText(149)}"></canvas>
                <div class="panel-file-manager__audio-glow"></div>
                <div class="panel-file-manager__audio-cover" aria-hidden="true"><span>${this.audioSvg('music', 58)}<b>${extension}</b></span></div>
                <div class="panel-file-manager__audio-track-copy"><span><i></i> ${fmText(150)}</span><div><strong dir="auto">${this.escape(item.name)}</strong>${this.audioSvg('equalizer')}</div><small><b>${extension}</b><b>${this.formatSize(item.size)}</b><b dir="ltr" data-audio-bitrate>${fmText(151)}</b></small></div>
                <div class="panel-file-manager__audio-viz-picker">
                    <button type="button" class="panel-file-manager__audio-viz-mode" data-audio-viz-mode aria-expanded="false" title="${fmText(152)}">${this.audioSvg('visualizer')}<span>${fmText(153)}</span>${this.audioSvg('chevron')}</button>
                    <div class="panel-file-manager__audio-viz-menu hidden" data-audio-viz-menu>
                        <button type="button" data-audio-viz-option="spectrum"><i></i><span><b>${fmText(154)}</b><small>${fmText(155)}</small></span></button>
                        <button type="button" data-audio-viz-option="wave"><i></i><span><b>${fmText(156)}</b><small>${fmText(157)}</small></span></button>
                        <button type="button" data-audio-viz-option="pulse"><i></i><span><b>${fmText(158)}</b><small>${fmText(159)}</small></span></button>
                        <button type="button" data-audio-viz-option="aurora"><i></i><span><b>${fmText(160)}</b><small>${fmText(161)}</small></span></button>
                        <button type="button" data-audio-viz-option="radial"><i></i><span><b>${fmText(162)}</b><small>${fmText(163)}</small></span></button>
                        <button type="button" class="is-active" data-audio-viz-option="particles"><i></i><span><b>${fmText(153)}</b><small>${fmText(164)}</small></span></button>
                        <button type="button" data-audio-viz-option="laser"><i></i><span><b>${fmText(165)}</b><small>${fmText(166)}</small></span></button>
                        <button type="button" data-audio-viz-option="ribbons"><i></i><span><b>${fmText(167)}</b><small>${fmText(168)}</small></span></button>
                        <button type="button" data-audio-viz-option="rain"><i></i><span><b>${fmText(169)}</b><small>${fmText(170)}</small></span></button>
                    </div>
                </div>
                <div class="panel-file-manager__audio-timeline panel-file-manager__audio-hero-timeline">
                    <span data-audio-current>${fmText(98)}</span>
                    <label><span class="panel-file-manager__audio-buffer" data-audio-buffer></span><input type="range" min="0" max="100" value="0" step="0.01" data-audio-seek aria-label="${fmText(171)}"></label>
                    <span data-audio-duration>${fmText(98)}</span>
                </div>
            </section>
            <section class="panel-file-manager__audio-controls">
                <div class="panel-file-manager__audio-command-row">
                    <div class="panel-file-manager__audio-secondary-controls is-leading">
                        <button type="button" data-audio-repeat aria-pressed="false" title="${fmText(68)}">${this.audioSvg('repeat')}<span>${fmText(100)}</span></button>
                        <button type="button" data-audio-region-repeat aria-pressed="false" title="${fmText(172)} A ${fmText(173)} B">${this.audioSvg('region')}<span>${fmText(174)}</span></button>
                        <button type="button" data-audio-back title="${fmText(175)}">${this.audioSvg('back')}<span>${fmText(103)}</span></button>
                    </div>
                    <button type="button" class="panel-file-manager__audio-play" data-audio-play aria-label="${fmText(107)}" aria-pressed="false">${this.audioSvg('play')}</button>
                    <div class="panel-file-manager__audio-secondary-controls is-trailing">
                        <button type="button" data-audio-forward title="${fmText(176)}">${this.audioSvg('forward')}<span>${fmText(110)}+</span></button>
                        <button type="button" data-audio-restart title="${fmText(177)}">${this.audioSvg('previous')}<span>${fmText(178)}</span></button>
                        <button type="button" data-audio-next title="${fmText(179)}">${this.audioSvg('next')}<span>${fmText(180)}</span></button>
                        <div class="panel-file-manager__audio-speed">
                            <button type="button" data-audio-speed-trigger aria-expanded="false" aria-haspopup="listbox" aria-label="${fmText(181)}">${this.audioSvg('speed')}<span>${fmText(182)}</span><b dir="ltr" data-audio-speed-value>1×</b>${this.audioSvg('chevron')}</button>
                            <select class="hidden" data-audio-speed aria-hidden="true" tabindex="-1"><option value="0.75">${fmText(124)}×</option><option value="1" selected>${fmText(95)}×</option><option value="1.25">${fmText(125)}×</option><option value="1.5">${fmText(126)}×</option><option value="2">${fmText(127)}×</option></select>
                            <div class="panel-file-manager__audio-speed-menu hidden" data-audio-speed-menu role="listbox" aria-label="${fmText(121)}">
                                <span><strong>${fmText(121)}</strong><small>${fmText(183)}</small></span>
                                <button type="button" data-audio-speed-option="0.75" role="option"><span><b dir="ltr">0.75×</b><small>${fmText(130)}</small></span><i></i></button>
                                <button type="button" class="is-active" data-audio-speed-option="1" role="option" aria-selected="true"><span><b dir="ltr">1×</b><small>${fmText(131)}</small></span><i></i></button>
                                <button type="button" data-audio-speed-option="1.25" role="option"><span><b dir="ltr">1.25×</b><small>${fmText(132)}</small></span><i></i></button>
                                <button type="button" data-audio-speed-option="1.5" role="option"><span><b dir="ltr">1.5×</b><small>${fmText(133)}</small></span><i></i></button>
                                <button type="button" data-audio-speed-option="2" role="option"><span><b dir="ltr">2×</b><small>${fmText(134)}</small></span><i></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-file-manager__audio-utilities">
                    <div class="panel-file-manager__audio-volume"><button type="button" data-audio-mute aria-pressed="false" title="${fmText(117)}">${this.audioSvg('volume')}<span>${fmText(118)}</span></button><input type="range" min="0" max="1" step="0.01" value="0.85" data-audio-volume aria-label="${fmText(119)}"><output data-audio-volume-value>${fmText(120)}</output></div>
                    <div class="panel-file-manager__audio-ab"><span>${this.audioSvg('region')}<b>${fmText(184)}</b></span><button type="button" data-audio-loop-start title="${fmText(185)}">A</button><button type="button" data-audio-loop-end title="${fmText(186)}">B</button><button type="button" data-audio-loop-clear title="${fmText(187)}">${this.audioSvg('close')}</button><output data-audio-loop-status>${fmText(188)}</output></div>
                </div>
            </section>
            <section class="panel-file-manager__audio-sound-lab">
                <section class="panel-file-manager__equalizer" data-audio-equalizer>
                    <header><div>${this.audioSvg('equalizer')}<span><strong>${fmText(189)}</strong><small>${fmText(190)}</small></span></div><span class="panel-file-manager__eq-energy" data-audio-eq-energy aria-hidden="true">${Array.from({ length: 12 }, () => '<i></i>').join('')}</span><button type="button" data-audio-eq-toggle aria-pressed="true"><i></i></button></header>
                    <div class="panel-file-manager__eq-bands">
                        <span class="panel-file-manager__eq-axis" aria-hidden="true"><b>+12</b><b>0</b><b>−12</b></span>
                        <svg class="panel-file-manager__eq-curve" data-audio-eq-curve viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><defs><linearGradient id="fm-audio-eq-gradient" x1="0" x2="1"><stop offset="0" stop-color="#35d6d0"/><stop offset=".45" stop-color="#5d7df2"/><stop offset="1" stop-color="#b65cff"/></linearGradient></defs><path data-audio-eq-path d="M 6.25 50 L 93.75 50"/></svg>
                        ${[60, 170, 310, 600, 1000, 3000, 6000, 12000].map((frequency) => `<label style="--eq-level:50%"><output data-audio-band-value="${frequency}">${fmText(191)}</output><span class="panel-file-manager__eq-slider"><i></i><input type="range" min="-12" max="12" step="1" value="0" data-audio-band="${frequency}" aria-label="${fmText(192)} ${frequency} ${fmText(193)}"></span><span class="panel-file-manager__eq-frequency" dir="ltr">${frequency >= 1000 ? `${frequency / 1000}K` : frequency}</span></label>`).join('')}
                    </div>
                    <div class="panel-file-manager__eq-presets" data-audio-presets>
                        <button type="button" class="is-active" data-audio-preset="flat" aria-label="${fmText(194)}" data-tooltip="${fmText(194)}">${this.audioSvg('equalizer')}</button><button type="button" data-audio-preset="bass" aria-label="${fmText(195)}" data-tooltip="${fmText(195)}">${this.audioSvg('bass')}</button><button type="button" data-audio-preset="vocal" aria-label="${fmText(196)}" data-tooltip="${fmText(196)}">${this.audioSvg('microphone')}</button><button type="button" data-audio-preset="clarity" aria-label="${fmText(197)}" data-tooltip="${fmText(197)}">${this.audioSvg('sparkles')}</button><button type="button" data-audio-preset="electronic" aria-label="${fmText(198)}" data-tooltip="${fmText(198)}">${this.audioSvg('visualizer')}</button><button type="button" data-audio-preset="rock" aria-label="${fmText(199)}" data-tooltip="${fmText(199)}">${this.audioSvg('bolt')}</button>
                        <div class="panel-file-manager__eq-more"><button type="button" data-audio-presets-more aria-expanded="false" aria-label="${fmText(200)}" data-tooltip="${fmText(200)}">${this.audioSvg('add')}</button><div class="hidden" data-audio-presets-menu><button type="button" data-audio-preset="podcast">${this.audioSvg('broadcast')}<span>${fmText(201)}</span></button><button type="button" data-audio-preset="cinema">${this.audioSvg('film')}<span>${fmText(202)}</span></button></div></div>
                    </div>
                    <output class="panel-file-manager__eq-status" data-audio-eq-status>${fmText(203)}</output>
                </section>
                <section class="panel-file-manager__audio-effects" aria-label="${fmText(204)}">
                    <header><span>${this.audioSvg('sparkles')}<i><strong>${fmText(205)}</strong><small>${fmText(206)}</small></i></span><button type="button" data-audio-studio-master aria-pressed="false"><i></i></button></header>
                    <div class="panel-file-manager__audio-effect-rack">
                        <button type="button" data-audio-effect="night" aria-pressed="false">${this.audioSvg('night')}<span><strong>${fmText(207)}</strong><small>${fmText(208)}</small></span><b>${fmText(209)}</b></button>
                        <button type="button" data-audio-effect="warmth" aria-pressed="false">${this.audioSvg('warmth')}<span><strong>${fmText(210)}</strong><small>${fmText(211)}</small></span><b>${fmText(209)}</b></button>
                        <button type="button" data-audio-effect="reverb" aria-pressed="false">${this.audioSvg('reverb')}<span><strong>${fmText(212)}</strong><small>${fmText(213)}</small></span><b>${fmText(209)}</b></button>
                        <button type="button" data-audio-effect="bass" aria-pressed="false">${this.audioSvg('bass')}<span><strong>${fmText(214)}</strong><small>${fmText(215)}</small></span><b>${fmText(209)}</b></button>
                    </div>
                    <button type="button" class="panel-file-manager__audio-advanced" data-audio-advanced><span>${this.audioSvg('settings')} ${fmText(216)}</span><b>Echo / Reverb</b></button>
                </section>
                <section class="panel-file-manager__audio-smart-tools">
                    <button type="button" class="panel-file-manager__audio-normalize" data-audio-normalize aria-pressed="false"><span>${this.audioSvg('normalize')}<i><strong>${fmText(217)}</strong><small>${fmText(218)}</small></i></span><b>${fmText(209)}</b><div class="panel-file-manager__audio-lufs"><span data-audio-level>${Array.from({ length: 12 }, () => '<i></i>').join('')}</span><output dir="ltr" data-audio-lufs>−∞ LUFS</output></div></button>
                    <section class="panel-file-manager__audio-extra-fx" data-audio-extra-fx>
                        <header><span>${this.audioSvg('sparkles')}<i><strong>${fmText(219)}</strong><small>Echo ${fmText(70)} Reverb ${fmText(220)}</small></i></span><button type="button" data-audio-fx-master aria-pressed="false"><i></i></button></header>
                        <div><label>${this.audioSvg('echo')}<select data-audio-effect-mode="echo" aria-label="${fmText(51)} Echo"><option value="off">Echo ${fmText(209)}</option><option value="mild">${fmText(221)}</option><option value="deep">${fmText(222)}</option></select></label><label>${this.audioSvg('reverb')}<select data-audio-effect-mode="reverb" aria-label="${fmText(51)} Reverb"><option value="off">Reverb ${fmText(209)}</option><option value="room">${fmText(223)}</option><option value="hall">${fmText(224)}</option></select></label></div>
                        <label><span>${fmText(225)}</span><input type="range" min="-1" max="1" step="0.05" value="0" data-audio-balance aria-label="${fmText(226)}"><output data-audio-balance-value>${fmText(227)}</output></label>
                    </section>
                </section>
            </section>
        </div>`;

        const player = stage.querySelector('[data-audio-player]');
        const audio = player.querySelector('[data-audio-element]');
        const state = {
            item, player, audio,
            canvas: player.querySelector('[data-audio-visualizer]'),
            context: null, source: null, analyser: null, filters: [], compressor: null, panner: null, animationFrame: null,
            bassBoost: null, warmth: null, toneFilter: null, dryGain: null, convolver: null, reverbGain: null,
            delay: null, echoGain: null, echoFeedback: null,
            gains: [0, 0, 0, 0, 0, 0, 0, 0], vizMode: 'particles', scrubbing: false, normalize: false, eqEnabled: true,
            loopStart: null, loopEnd: null, regionLoop: false,
            effects: { bass: false, warmth: false, reverb: false, echo: false, night: false },
            effectModes: { echo: 'off', reverb: 'off' },
        };
        this.audioPreviewState = state;
        audio.volume = .85;
        this.bindAudioPlayer(state);
        this.drawAudioVisualizer(state);
        this.startAudioPlayback(state);
    },

    audioSvg(name, size = 20) {
        const icons = {
            play: 'play', pause: 'pause', back: 'rewind-10', forward: 'forward-10', repeat: 'repeat',
            volume: 'volume', muted: 'volume-off', bass: 'activity', warmth: 'sun', reverb: 'wifi', echo: 'radio',
            night: 'moon', music: 'headphones', visualizer: 'chart-bar', region: 'layout', restart: 'refresh',
            previous: 'arrow-left', next: 'arrow-right', close: 'x', equalizer: 'activity', sparkles: 'energy',
            normalize: 'activity', balance: 'arrows-left-right', settings: 'settings', chevron: 'chevron-down',
            speed: 'gauge', microphone: 'microphone', bolt: 'energy', broadcast: 'wifi', film: 'video', add: 'plus',
        };
        return iconMarkup(icons[name] || 'play', size);
    },

    bindAudioPlayer(state) {
        const { player, audio } = state;
        const play = player.querySelector('[data-audio-play]');
        const seek = player.querySelector('[data-audio-seek]');
        const volume = player.querySelector('[data-audio-volume]');
        const mute = player.querySelector('[data-audio-mute]');
        const repeat = player.querySelector('[data-audio-repeat]');
        const speed = player.querySelector('[data-audio-speed]');
        const speedTrigger = player.querySelector('[data-audio-speed-trigger]');
        const speedMenu = player.querySelector('[data-audio-speed-menu]');
        const normalize = player.querySelector('[data-audio-normalize]');
        const balance = player.querySelector('[data-audio-balance]');
        play.addEventListener('click', () => this.toggleAudioPlayback(state));
        player.querySelector('[data-audio-back]').addEventListener('click', () => { audio.currentTime = Math.max(0, audio.currentTime - 10); });
        player.querySelector('[data-audio-forward]').addEventListener('click', () => { audio.currentTime = Math.min(audio.duration || Infinity, audio.currentTime + 10); });
        player.querySelector('[data-audio-restart]').addEventListener('click', () => { audio.currentTime = 0; });
        player.querySelector('[data-audio-next]').addEventListener('click', () => { if (Number.isFinite(audio.duration)) audio.currentTime = Math.max(0, audio.duration - .05); });
        player.querySelector('[data-audio-region-repeat]').addEventListener('click', (event) => {
            if (state.loopStart === null || state.loopEnd === null) {
                this.toast(`${fmText(228)} A ${fmText(70)} B ${fmText(229)}`, 'warning');
                return;
            }
            state.regionLoop = !state.regionLoop;
            event.currentTarget.classList.toggle('is-active', state.regionLoop);
            event.currentTarget.setAttribute('aria-pressed', state.regionLoop ? 'true' : 'false');
        });
        seek.addEventListener('pointerdown', () => { state.scrubbing = true; });
        seek.addEventListener('input', () => {
            if (Number.isFinite(audio.duration)) audio.currentTime = (Number(seek.value) / 100) * audio.duration;
            this.updateAudioTimeline(state);
        });
        seek.addEventListener('change', () => { state.scrubbing = false; });
        volume.addEventListener('input', () => {
            audio.volume = Number(volume.value);
            audio.muted = false;
            this.updateAudioVolume(state);
        });
        mute.addEventListener('click', () => { audio.muted = !audio.muted; this.updateAudioVolume(state); });
        repeat.addEventListener('click', () => {
            audio.loop = !audio.loop;
            repeat.classList.toggle('is-active', audio.loop);
            repeat.setAttribute('aria-pressed', audio.loop ? 'true' : 'false');
        });
        const updateSpeed = () => {
            audio.playbackRate = Number(speed.value);
            const label = player.querySelector('[data-audio-speed-value]');
            if (label) label.textContent = `${Number(speed.value).toLocaleString('en-US', { maximumFractionDigits: 2 })}×`;
            player.querySelectorAll('[data-audio-speed-option]').forEach((option) => {
                const active = option.dataset.audioSpeedOption === speed.value;
                option.classList.toggle('is-active', active);
                option.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        };
        speed.addEventListener('change', updateSpeed);
        speedTrigger.addEventListener('click', () => {
            const expanded = speedTrigger.getAttribute('aria-expanded') === 'true';
            if (!expanded) {
                const triggerBox = speedTrigger.getBoundingClientRect();
                const previewBox = this.elements.previewStage?.getBoundingClientRect();
                speedMenu.classList.toggle('is-drop-down', triggerBox.top - (previewBox?.top || 0) < 265);
            }
            speedTrigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            speedMenu.classList.toggle('hidden', expanded);
        });
        player.querySelectorAll('[data-audio-speed-option]').forEach((option) => option.addEventListener('click', () => {
            speed.value = option.dataset.audioSpeedOption;
            updateSpeed();
            speedTrigger.setAttribute('aria-expanded', 'false');
            speedMenu.classList.add('hidden');
            speedTrigger.focus();
        }));
        normalize.addEventListener('click', async () => {
            state.normalize = !state.normalize;
            await this.ensureAudioGraph(state);
            if (state.compressor) {
                state.compressor.threshold.value = state.normalize ? -24 : 0;
                state.compressor.knee.value = state.normalize ? 28 : 0;
                state.compressor.ratio.value = state.normalize ? 4 : 1;
                state.compressor.attack.value = state.normalize ? .006 : 0;
                state.compressor.release.value = state.normalize ? .24 : .05;
            }
            normalize.classList.toggle('is-active', state.normalize);
            normalize.setAttribute('aria-pressed', state.normalize ? 'true' : 'false');
            normalize.querySelector('b').textContent = state.normalize ? `${fmText(230)}` : `${fmText(209)}`;
        });
        balance.addEventListener('input', async () => {
            await this.ensureAudioGraph(state);
            if (state.panner) state.panner.pan.value = Number(balance.value);
            const value = Number(balance.value);
            player.querySelector('[data-audio-balance-value]').textContent = Math.abs(value) < .05 ? `${fmText(227)}` : `${Math.round(Math.abs(value) * 100).toLocaleString(document.documentElement.lang || 'fa')}${fmText(92)} ${value < 0 ? `${fmText(231)}` : `${fmText(232)}`}`;
        });
        player.querySelector('[data-audio-loop-start]').addEventListener('click', () => this.setAudioLoopPoint(state, 'start'));
        player.querySelector('[data-audio-loop-end]').addEventListener('click', () => this.setAudioLoopPoint(state, 'end'));
        player.querySelector('[data-audio-loop-clear]').addEventListener('click', () => this.clearAudioLoop(state));
        const vizTrigger = player.querySelector('[data-audio-viz-mode]');
        const vizMenu = player.querySelector('[data-audio-viz-menu]');
        const vizLabels = { spectrum: `${fmText(154)}`, wave: `${fmText(156)}`, pulse: `${fmText(158)}`, aurora: `${fmText(160)}`, radial: `${fmText(162)}`, particles: `${fmText(153)}`, laser: `${fmText(165)}`, ribbons: `${fmText(167)}`, rain: `${fmText(169)}` };
        vizTrigger.addEventListener('click', () => {
            const expanded = vizTrigger.getAttribute('aria-expanded') === 'true';
            vizTrigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            vizMenu.classList.toggle('hidden', expanded);
            speedTrigger.setAttribute('aria-expanded', 'false');
            speedMenu.classList.add('hidden');
        });
        player.querySelectorAll('[data-audio-viz-option]').forEach((button) => button.addEventListener('click', () => {
            state.vizMode = button.dataset.audioVizOption;
            player.querySelectorAll('[data-audio-viz-option]').forEach((option) => option.classList.toggle('is-active', option === button));
            vizTrigger.querySelector('span').textContent = vizLabels[state.vizMode];
            vizTrigger.setAttribute('aria-expanded', 'false');
            vizMenu.classList.add('hidden');
        }));
        player.querySelectorAll('[data-audio-effect]').forEach((button) => button.addEventListener('click', () => this.toggleAudioEffect(state, button.dataset.audioEffect, button)));
        player.querySelector('[data-audio-studio-master]').addEventListener('click', async () => {
            const studioEffects = ['night', 'warmth', 'reverb', 'bass'];
            const activeEffects = studioEffects.filter((effect) => state.effects[effect]);
            if (activeEffects.length) {
                for (const effect of activeEffects) await this.toggleAudioEffect(state, effect);
            } else {
                await this.toggleAudioEffect(state, 'warmth');
            }
        });
        player.querySelectorAll('[data-audio-effect-mode]').forEach((select) => select.addEventListener('change', async () => {
            const effect = select.dataset.audioEffectMode;
            state.effectModes[effect] = select.value;
            const shouldBeActive = select.value !== 'off';
            if (state.effects[effect] !== shouldBeActive) await this.toggleAudioEffect(state, effect);
            else {
                await this.ensureAudioGraph(state);
                this.applyAudioEffectMode(state, effect);
                this.updateAudioEffectControls(state);
            }
        }));
        player.querySelector('[data-audio-fx-master]').addEventListener('click', async () => {
            const active = state.effects.echo || state.effects.reverb;
            if (active) {
                if (state.effects.echo) await this.toggleAudioEffect(state, 'echo');
                if (state.effects.reverb) await this.toggleAudioEffect(state, 'reverb');
            } else {
                await this.toggleAudioEffect(state, 'reverb');
            }
        });
        player.querySelector('[data-audio-advanced]').addEventListener('click', () => {
            const panel = player.querySelector('[data-audio-extra-fx]');
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            panel.classList.remove('is-highlighted');
            requestAnimationFrame(() => panel.classList.add('is-highlighted'));
            setTimeout(() => panel.classList.remove('is-highlighted'), 900);
        });
        player.querySelectorAll('[data-audio-preset]').forEach((button) => button.addEventListener('click', () => this.applyAudioPreset(state, button.dataset.audioPreset)));
        const presetMore = player.querySelector('[data-audio-presets-more]');
        const presetMenu = player.querySelector('[data-audio-presets-menu]');
        presetMore.addEventListener('click', () => {
            const expanded = presetMore.getAttribute('aria-expanded') === 'true';
            presetMore.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            presetMenu.classList.toggle('hidden', expanded);
        });
        presetMenu.addEventListener('click', () => {
            presetMenu.classList.add('hidden');
            presetMore.setAttribute('aria-expanded', 'false');
        });
        player.addEventListener('click', (event) => {
            if (event.target.closest('.panel-file-manager__eq-more')) return;
            presetMenu.classList.add('hidden');
            presetMore.setAttribute('aria-expanded', 'false');
            if (!event.target.closest('.panel-file-manager__audio-speed')) {
                speedMenu.classList.add('hidden');
                speedTrigger.setAttribute('aria-expanded', 'false');
            }
            if (!event.target.closest('.panel-file-manager__audio-viz-picker')) {
                vizMenu.classList.add('hidden');
                vizTrigger.setAttribute('aria-expanded', 'false');
            }
        });
        player.querySelectorAll('[data-audio-band]').forEach((input, index) => input.addEventListener('input', () => {
            state.gains[index] = Number(input.value);
            if (state.filters[index] && state.eqEnabled) state.filters[index].gain.value = state.gains[index];
            player.querySelector(`[data-audio-band-value="${input.dataset.audioBand}"]`).textContent = state.gains[index] > 0 ? `+${state.gains[index]}` : String(state.gains[index]);
            this.updateAudioEqBandVisual(input, state.gains[index]);
            player.querySelectorAll('[data-audio-preset]').forEach((button) => button.classList.remove('is-active'));
            player.querySelector('[data-audio-eq-status]').textContent = `${fmText(233)}`;
            this.updateAudioEqCurve(state);
        }));
        player.querySelector('[data-audio-eq-toggle]').addEventListener('click', async (event) => {
            state.eqEnabled = !state.eqEnabled;
            await this.ensureAudioGraph(state);
            state.filters.forEach((filter, index) => filter.gain.setTargetAtTime(state.eqEnabled ? state.gains[index] : 0, state.context.currentTime, .035));
            event.currentTarget.classList.toggle('is-off', !state.eqEnabled);
            event.currentTarget.setAttribute('aria-pressed', state.eqEnabled ? 'true' : 'false');
            this.updateAudioEqCurve(state);
        });
        audio.addEventListener('loadedmetadata', () => this.updateAudioTimeline(state));
        audio.addEventListener('durationchange', () => this.updateAudioTimeline(state));
        audio.addEventListener('timeupdate', () => this.updateAudioTimeline(state));
        audio.addEventListener('progress', () => this.updateAudioTimeline(state));
        audio.addEventListener('play', () => this.updateAudioPlaybackState(state));
        audio.addEventListener('pause', () => this.updateAudioPlaybackState(state));
        audio.addEventListener('ended', () => this.updateAudioPlaybackState(state));
        audio.addEventListener('error', () => this.toast(`${fmText(234)}`, 'error'));
        player.addEventListener('keydown', (event) => {
            if (['INPUT', 'SELECT'].includes(event.target.tagName)) return;
            const handled = ['Space', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'KeyM', 'KeyR', 'BracketLeft', 'BracketRight'].includes(event.code);
            if (!handled) return;
            event.preventDefault();
            event.stopPropagation();
            if (event.code === 'Space') this.toggleAudioPlayback(state);
            if (event.key === 'ArrowLeft') audio.currentTime = Math.max(0, audio.currentTime - 5);
            if (event.key === 'ArrowRight') audio.currentTime = Math.min(audio.duration || Infinity, audio.currentTime + 5);
            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                audio.muted = false;
                audio.volume = Math.max(0, Math.min(1, audio.volume + (event.key === 'ArrowUp' ? .05 : -.05)));
                this.updateAudioVolume(state);
            }
            if (event.key.toLowerCase() === 'm') { audio.muted = !audio.muted; this.updateAudioVolume(state); }
            if (event.key.toLowerCase() === 'r') repeat.click();
            if (event.code === 'BracketLeft') this.setAudioLoopPoint(state, 'start');
            if (event.code === 'BracketRight') this.setAudioLoopPoint(state, 'end');
        });
        this.updateAudioPlaybackState(state);
        this.updateAudioVolume(state);
        this.updateAudioEqCurve(state);
    },

    async startAudioPlayback(state) {
        try {
            await this.ensureAudioGraph(state);
            await state.audio.play();
        } catch {
            this.updateAudioPlaybackState(state);
        }
    },

    async toggleAudioPlayback(state) {
        if (!state?.audio) return;
        if (state.audio.paused) {
            try {
                await this.ensureAudioGraph(state);
                await state.context?.resume?.();
                await state.audio.play();
            } catch {
                this.toast(`${fmText(235)}`, 'warning');
            }
        } else {
            state.audio.pause();
        }
    },

    async ensureAudioGraph(state) {
        if (state.context || this.audioPreviewState !== state) return;
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass) return;
        try {
            const context = new AudioContextClass();
            const source = context.createMediaElementSource(state.audio);
            const frequencies = [60, 170, 310, 600, 1000, 3000, 6000, 12000];
            const filters = frequencies.map((frequency, index) => {
                const filter = context.createBiquadFilter();
                filter.type = index === 0 ? 'lowshelf' : (index === frequencies.length - 1 ? 'highshelf' : 'peaking');
                filter.frequency.value = frequency;
                filter.Q.value = 1.15;
                filter.gain.value = state.gains[index];
                return filter;
            });
            const analyser = context.createAnalyser();
            analyser.fftSize = 512;
            analyser.smoothingTimeConstant = .82;
            const compressor = context.createDynamicsCompressor();
            compressor.threshold.value = state.normalize ? -24 : 0;
            compressor.knee.value = state.normalize ? 28 : 0;
            compressor.ratio.value = state.normalize ? 4 : 1;
            compressor.attack.value = state.normalize ? .006 : 0;
            compressor.release.value = state.normalize ? .24 : .05;
            const panner = typeof context.createStereoPanner === 'function' ? context.createStereoPanner() : null;
            const bassBoost = context.createBiquadFilter();
            bassBoost.type = 'lowshelf';
            bassBoost.frequency.value = 95;
            bassBoost.gain.value = state.effects.bass ? 8 : 0;
            const warmth = context.createWaveShaper();
            warmth.curve = this.audioWarmthCurve(state.effects.warmth ? 18 : 0);
            warmth.oversample = state.effects.warmth ? '2x' : 'none';
            const toneFilter = context.createBiquadFilter();
            toneFilter.type = 'lowpass';
            toneFilter.frequency.value = state.effects.night ? 4800 : Math.min(22000, context.sampleRate / 2);
            toneFilter.Q.value = state.effects.night ? .75 : .0001;
            const dryGain = context.createGain();
            dryGain.gain.value = 1;
            const convolver = context.createConvolver();
            convolver.buffer = this.createAudioImpulse(context, 1.65, 2.5);
            const reverbGain = context.createGain();
            reverbGain.gain.value = state.effects.reverb ? .24 : 0;
            const delay = context.createDelay(1.5);
            delay.delayTime.value = .24;
            const echoGain = context.createGain();
            echoGain.gain.value = state.effects.echo ? .2 : 0;
            const echoFeedback = context.createGain();
            echoFeedback.gain.value = state.effects.echo ? .28 : 0;
            source.connect(filters[0]);
            filters.forEach((filter, index) => filter.connect(filters[index + 1] || compressor));
            compressor.connect(bassBoost);
            bassBoost.connect(warmth);
            warmth.connect(toneFilter);
            toneFilter.connect(panner || dryGain);
            if (panner) panner.connect(dryGain);
            dryGain.connect(analyser);
            (panner || toneFilter).connect(convolver);
            convolver.connect(reverbGain);
            reverbGain.connect(analyser);
            (panner || toneFilter).connect(delay);
            delay.connect(echoGain);
            echoGain.connect(analyser);
            delay.connect(echoFeedback);
            echoFeedback.connect(delay);
            analyser.connect(context.destination);
            Object.assign(state, {
                context, source, filters, compressor, panner, analyser, bassBoost, warmth, toneFilter,
                dryGain, convolver, reverbGain, delay, echoGain, echoFeedback,
            });
            await context.resume();
        } catch {
            state.context = null;
            state.analyser = null;
        }
    },

    audioWarmthCurve(amount = 0) {
        const samples = 2048;
        const curve = new Float32Array(samples);
        const intensity = Math.max(0, Number(amount) || 0) / 10;
        for (let index = 0; index < samples; index += 1) {
            const value = ((index * 2) / (samples - 1)) - 1;
            curve[index] = intensity ? ((1 + intensity) * value) / (1 + (intensity * Math.abs(value))) : value;
        }
        return curve;
    },

    createAudioImpulse(context, duration = 1.6, decay = 2.4) {
        const length = Math.max(1, Math.floor(context.sampleRate * duration));
        const impulse = context.createBuffer(2, length, context.sampleRate);
        for (let channel = 0; channel < impulse.numberOfChannels; channel += 1) {
            const data = impulse.getChannelData(channel);
            for (let index = 0; index < length; index += 1) {
                const envelope = ((length - index) / length) ** decay;
                data[index] = ((Math.random() * 2) - 1) * envelope;
            }
        }
        return impulse;
    },

    async toggleAudioEffect(state, effect) {
        if (!Object.hasOwn(state.effects, effect)) return;
        state.effects[effect] = !state.effects[effect];
        await this.ensureAudioGraph(state);
        const active = state.effects[effect];
        const now = state.context?.currentTime || 0;
        if (effect === 'bass' && state.bassBoost) state.bassBoost.gain.setTargetAtTime(active ? 8 : 0, now, .04);
        if (effect === 'warmth' && state.warmth) {
            state.warmth.curve = this.audioWarmthCurve(active ? 18 : 0);
            state.warmth.oversample = active ? '2x' : 'none';
        }
        if (effect === 'reverb' && state.reverbGain) state.reverbGain.gain.setTargetAtTime(active ? .24 : 0, now, .04);
        if (effect === 'echo') {
            state.echoGain?.gain.setTargetAtTime(active ? .2 : 0, now, .04);
            state.echoFeedback?.gain.setTargetAtTime(active ? .28 : 0, now, .04);
        }
        if (effect === 'night' && state.toneFilter) {
            state.toneFilter.frequency.setTargetAtTime(active ? 4800 : Math.min(20000, state.context.sampleRate / 2), now, .045);
            state.toneFilter.Q.setTargetAtTime(active ? .75 : .0001, now, .045);
        }
        if (effect === 'echo' || effect === 'reverb') {
            if (active && state.effectModes[effect] === 'off') state.effectModes[effect] = effect === 'echo' ? 'mild' : 'room';
            if (!active) state.effectModes[effect] = 'off';
            this.applyAudioEffectMode(state, effect);
        }
        this.updateAudioEffectControls(state);
    },

    applyAudioEffectMode(state, effect) {
        const now = state.context?.currentTime || 0;
        const mode = state.effectModes?.[effect] || 'off';
        if (effect === 'echo') {
            const values = mode === 'deep' ? { delay: .38, gain: .28, feedback: .42 } : { delay: .24, gain: .2, feedback: .28 };
            if (state.delay) state.delay.delayTime.setTargetAtTime(values.delay, now, .04);
            if (state.echoGain) state.echoGain.gain.setTargetAtTime(mode === 'off' ? 0 : values.gain, now, .04);
            if (state.echoFeedback) state.echoFeedback.gain.setTargetAtTime(mode === 'off' ? 0 : values.feedback, now, .04);
        }
        if (effect === 'reverb' && state.reverbGain) {
            const gain = mode === 'hall' ? .36 : .24;
            state.reverbGain.gain.setTargetAtTime(mode === 'off' ? 0 : gain, now, .04);
        }
    },

    updateAudioEffectControls(state) {
        const { player } = state;
        Object.entries(state.effects).forEach(([effect, active]) => {
            player.querySelectorAll(`[data-audio-effect="${effect}"]`).forEach((button) => {
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
                const status = button.querySelector('b');
                if (status) status.textContent = active ? `${fmText(230)}` : `${fmText(209)}`;
            });
            const mode = player.querySelector(`[data-audio-effect-mode="${effect}"]`);
            if (mode) mode.value = state.effectModes[effect] || 'off';
        });
        const fxActive = state.effects.echo || state.effects.reverb;
        const fxMaster = player.querySelector('[data-audio-fx-master]');
        fxMaster?.classList.toggle('is-active', fxActive);
        fxMaster?.setAttribute('aria-pressed', fxActive ? 'true' : 'false');
        const studioActive = ['night', 'warmth', 'reverb', 'bass'].some((effect) => state.effects[effect]);
        const studioMaster = player.querySelector('[data-audio-studio-master]');
        studioMaster?.classList.toggle('is-active', studioActive);
        studioMaster?.setAttribute('aria-pressed', studioActive ? 'true' : 'false');
    },

    updateAudioEqCurve(state) {
        const path = state.player.querySelector('[data-audio-eq-path]');
        if (!path || !state.gains.length) return;
        const points = state.gains.map((gain, index) => ({
            x: ((index + .5) / state.gains.length) * 100,
            y: 50 - ((Math.max(-12, Math.min(12, gain)) / 12) * 31),
        }));
        let value = `M ${points[0].x.toFixed(2)} ${points[0].y.toFixed(2)}`;
        for (let index = 0; index < points.length - 1; index += 1) {
            const current = points[index];
            const next = points[index + 1];
            const midpoint = (current.x + next.x) / 2;
            value += ` C ${midpoint.toFixed(2)} ${current.y.toFixed(2)}, ${midpoint.toFixed(2)} ${next.y.toFixed(2)}, ${next.x.toFixed(2)} ${next.y.toFixed(2)}`;
        }
        path.setAttribute('d', value);
        path.closest('[data-audio-eq-curve]')?.classList.toggle('is-muted', !state.eqEnabled);
    },

    updateAudioEqBandVisual(input, value) {
        const level = ((Math.max(-12, Math.min(12, Number(value) || 0)) + 12) / 24) * 100;
        input.closest('label')?.style.setProperty('--eq-level', `${level}%`);
    },

    applyAudioPreset(state, preset) {
        const presets = {
            flat: { label: `${fmText(203)}`, gains: [0, 0, 0, 0, 0, 0, 0, 0] },
            bass: { label: `${fmText(236)}`, gains: [8, 6, 3, 1, 0, -1, -2, -2] },
            vocal: { label: `${fmText(237)}`, gains: [-3, -2, -1, 1, 5, 4, 2, 0] },
            clarity: { label: `${fmText(238)}`, gains: [-3, -1, 0, 1, 2, 5, 6, 7] },
            electronic: { label: `${fmText(239)}`, gains: [6, 4, 1, -1, 1, 3, 5, 6] },
            rock: { label: `${fmText(240)}`, gains: [5, 3, -1, -2, 1, 4, 5, 4] },
            podcast: { label: `${fmText(241)}`, gains: [-5, -3, -1, 2, 5, 6, 3, 0] },
            cinema: { label: `${fmText(242)}`, gains: [7, 5, 2, 0, 1, 2, 4, 6] },
        };
        const selected = presets[preset] || presets.flat;
        state.gains = [...selected.gains];
        state.player.querySelectorAll('[data-audio-preset]').forEach((button) => button.classList.toggle('is-active', button.dataset.audioPreset === preset));
        state.player.querySelector('[data-audio-eq-status]').textContent = selected.label;
        state.player.querySelectorAll('[data-audio-band]').forEach((input, index) => {
            input.value = String(state.gains[index]);
            const value = state.gains[index];
            state.player.querySelector(`[data-audio-band-value="${input.dataset.audioBand}"]`).textContent = value > 0 ? `+${value}` : String(value);
            this.updateAudioEqBandVisual(input, value);
            if (state.filters[index]) state.filters[index].gain.setTargetAtTime(state.eqEnabled ? value : 0, state.context.currentTime, .035);
        });
        this.updateAudioEqCurve(state);
    },

    updateAudioPlaybackState(state) {
        const playing = !state.audio.paused && !state.audio.ended;
        const button = state.player.querySelector('[data-audio-play]');
        state.player.classList.toggle('is-playing', playing);
        button.innerHTML = this.audioSvg(playing ? 'pause' : 'play');
        button.setAttribute('aria-label', playing ? `${fmText(243)}` : `${fmText(107)}`);
        button.setAttribute('aria-pressed', playing ? 'true' : 'false');
    },

    updateAudioVolume(state) {
        const { player, audio } = state;
        const muted = audio.muted || audio.volume === 0;
        const button = player.querySelector('[data-audio-mute]');
        player.querySelector('[data-audio-volume]').value = String(audio.volume);
        button.innerHTML = `${this.audioSvg(muted ? 'muted' : 'volume')}<span>${muted ? `${fmText(141)}` : `${fmText(118)}`}</span>`;
        button.classList.toggle('is-active', muted);
        button.setAttribute('aria-pressed', muted ? 'true' : 'false');
        player.querySelector('[data-audio-volume-value]').textContent = `${Math.round(audio.volume * 100).toLocaleString(document.documentElement.lang || 'fa')}${fmText(92)}`;
    },

    updateAudioTimeline(state) {
        const { player, audio } = state;
        if (!state.scrubbing && state.regionLoop && state.loopStart !== null && state.loopEnd !== null && audio.currentTime >= state.loopEnd) audio.currentTime = state.loopStart;
        const duration = Number.isFinite(audio.duration) ? audio.duration : 0;
        const current = Number.isFinite(audio.currentTime) ? audio.currentTime : 0;
        const progress = duration ? (current / duration) * 100 : 0;
        const seek = player.querySelector('[data-audio-seek]');
        if (!state.scrubbing) seek.value = String(progress);
        seek.style.setProperty('--audio-played', `${progress}%`);
        player.querySelector('[data-audio-current]').textContent = this.formatAudioTime(current);
        player.querySelector('[data-audio-duration]').textContent = this.formatAudioTime(duration);
        const bitrate = player.querySelector('[data-audio-bitrate]');
        if (bitrate && duration > 0 && Number(state.item?.size) > 0) {
            const kbps = Math.max(1, Math.round((Number(state.item.size) * 8) / duration / 1000));
            bitrate.textContent = `${kbps.toLocaleString('en-US')} kbps`;
        }
        let buffered = 0;
        if (duration && audio.buffered.length) buffered = (audio.buffered.end(audio.buffered.length - 1) / duration) * 100;
        player.querySelector('[data-audio-buffer]').style.width = `${Math.min(100, buffered)}%`;
    },

    formatAudioTime(seconds) {
        const safe = Math.max(0, Math.floor(Number(seconds) || 0));
        const minutes = Math.floor(safe / 60);
        const remainder = safe % 60;
        return `${minutes.toLocaleString(document.documentElement.lang || 'en', { minimumIntegerDigits: 2 })}:${remainder.toLocaleString(document.documentElement.lang || 'en', { minimumIntegerDigits: 2 })}`;
    },

    setAudioLoopPoint(state, point) {
        const current = Number(state.audio.currentTime || 0);
        if (point === 'start') {
            state.loopStart = current;
            if (state.loopEnd !== null && state.loopEnd <= current) state.loopEnd = null;
        } else {
            if (state.loopStart === null) {
                this.toast(`${fmText(244)} A ${fmText(229)}`, 'warning');
                return;
            }
            if (current <= state.loopStart + .1) {
                this.toast(`${fmText(245)} B ${fmText(246)} A ${fmText(247)}`, 'warning');
                return;
            }
            state.loopEnd = current;
            state.regionLoop = true;
        }
        this.updateAudioLoopState(state);
    },

    clearAudioLoop(state) {
        state.loopStart = null;
        state.loopEnd = null;
        state.regionLoop = false;
        this.updateAudioLoopState(state);
    },

    updateAudioLoopState(state) {
        const { player } = state;
        player.querySelector('[data-audio-loop-start]').classList.toggle('is-active', state.loopStart !== null);
        player.querySelector('[data-audio-loop-end]').classList.toggle('is-active', state.loopEnd !== null);
        const region = player.querySelector('[data-audio-region-repeat]');
        if (region) {
            const active = state.regionLoop && state.loopStart !== null && state.loopEnd !== null;
            region.classList.toggle('is-active', active);
            region.setAttribute('aria-pressed', active ? 'true' : 'false');
        }
        const start = state.loopStart === null ? 'A' : this.formatAudioTime(state.loopStart);
        const end = state.loopEnd === null ? 'B' : this.formatAudioTime(state.loopEnd);
        player.querySelector('[data-audio-loop-status]').textContent = state.loopStart === null ? `${fmText(188)}` : `${start} ← ${end}`;
    },

    drawAudioVisualizer(state) {
        if (!state?.canvas || this.audioPreviewState !== state) return;
        const canvas = state.canvas;
        const rect = canvas.getBoundingClientRect();
        const ratio = Math.min(2, window.devicePixelRatio || 1);
        const width = Math.max(320, Math.floor(rect.width * ratio));
        const height = Math.max(140, Math.floor(rect.height * ratio));
        if (canvas.width !== width || canvas.height !== height) { canvas.width = width; canvas.height = height; }
        const context = canvas.getContext('2d');
        context.clearRect(0, 0, width, height);
        const timeValues = new Uint8Array(state.analyser?.fftSize || 512);
        if (state.analyser) state.analyser.getByteTimeDomainData(timeValues);
        else timeValues.forEach((_, index) => { timeValues[index] = 128 + Math.sin((index / 8) + (Date.now() / 360)) * 22; });
        const rms = Math.sqrt(timeValues.reduce((sum, value) => sum + (((value - 128) / 128) ** 2), 0) / timeValues.length);
        const instantLufs = rms > .0001 ? Math.max(-60, (20 * Math.log10(rms)) - .7) : -60;
        state.lufs = Number.isFinite(state.lufs) ? ((state.lufs * .88) + (instantLufs * .12)) : instantLufs;
        const lufsOutput = state.player.querySelector('[data-audio-lufs]');
        if (lufsOutput) lufsOutput.textContent = state.lufs <= -59 ? '−∞ LUFS' : `${Math.round(state.lufs)} LUFS`;
        const activeSegments = Math.max(0, Math.min(12, Math.round(((state.lufs + 60) / 54) * 12)));
        state.player.querySelectorAll('[data-audio-level] > i').forEach((segment, index) => segment.classList.toggle('is-active', index < activeSegments));

        const played = Number.isFinite(state.audio.duration) && state.audio.duration > 0 ? state.audio.currentTime / state.audio.duration : 0;
        const playedGradient = context.createLinearGradient(0, 0, width, 0);
        playedGradient.addColorStop(0, '#22d3c5');
        playedGradient.addColorStop(.36, '#38a4ff');
        playedGradient.addColorStop(.7, '#7167f5');
        playedGradient.addColorStop(1, '#d946ef');
        const idleGradient = context.createLinearGradient(0, 0, width, 0);
        idleGradient.addColorStop(0, 'rgba(34,211,197,.52)');
        idleGradient.addColorStop(.45, 'rgba(72,127,238,.48)');
        idleGradient.addColorStop(1, 'rgba(192,67,232,.52)');
        const frequencies = new Uint8Array(state.analyser?.frequencyBinCount || 256);
        if (state.analyser) state.analyser.getByteFrequencyData(frequencies);
        state.player.querySelectorAll('[data-audio-eq-energy] > i').forEach((segment, index, segments) => {
            const frequencyIndex = Math.min(frequencies.length - 1, Math.floor(((index + 1) / segments.length) ** 1.8 * Math.min(frequencies.length - 1, 190)));
            const live = frequencies[frequencyIndex] / 255;
            const idle = .16 + (Math.abs(Math.sin((index * .83) + (Date.now() / 620))) * .22);
            const energy = state.audio.paused ? idle : Math.max(idle * .45, live);
            segment.style.setProperty('--eq-energy', `${Math.round(22 + (energy * 78))}%`);
            segment.classList.toggle('is-hot', energy > .58);
        });
        if (state.vizMode === 'wave') {
            const drawWave = (strokeStyle, scale = 1, phase = 0, opacity = 1) => {
                context.globalAlpha = opacity;
                context.strokeStyle = strokeStyle;
                context.beginPath();
                timeValues.forEach((value, index) => {
                    const x = (index / (timeValues.length - 1)) * width;
                    const sample = (value - 128) / 128;
                    const y = (height / 2) + (sample * height * .42 * scale) + (Math.sin((index * .055) + phase) * height * .025);
                    if (!index) context.moveTo(x, y); else context.lineTo(x, y);
                });
                context.stroke();
            };
            context.lineCap = 'round';
            context.lineJoin = 'round';
            context.lineWidth = 1.1 * ratio;
            drawWave(idleGradient, .72, 2.1, .42);
            context.lineWidth = 1.7 * ratio;
            drawWave(idleGradient, .88, 1.1, .62);
            context.lineWidth = 2.2 * ratio;
            drawWave(idleGradient, 1, 0, .92);
            context.save();
            context.beginPath();
            context.rect(0, 0, width * played, height);
            context.clip();
            context.globalAlpha = 1;
            context.shadowBlur = 16 * ratio;
            context.shadowColor = 'rgba(93,125,242,.45)';
            drawWave(playedGradient, 1, 0, 1);
            context.restore();
            context.globalAlpha = 1;
        } else if (state.vizMode === 'pulse') {
            const bars = 96;
            const gap = 2.4 * ratio;
            const barWidth = Math.max(2 * ratio, (width - ((bars - 1) * gap)) / bars);
            const baseline = height * .86;
            for (let index = 0; index < bars; index += 1) {
                const frequencyIndex = Math.min(frequencies.length - 1, Math.floor(((index / (bars - 1)) ** 1.7) * Math.min(frequencies.length - 1, 210)));
                const live = frequencies[frequencyIndex] / 255;
                const idle = .12 + (Math.abs(Math.sin((index * .31) + .8)) * .5 * (1 - (index / bars) * .55));
                const amplitude = state.audio.paused ? idle : Math.max(idle * .35, live);
                const barHeight = Math.max(7 * ratio, amplitude * height * .72);
                const x = index * (barWidth + gap);
                context.fillStyle = (index / bars) <= played ? playedGradient : idleGradient;
                context.shadowBlur = amplitude > .55 ? 11 * ratio : 0;
                context.shadowColor = 'rgba(46,150,246,.4)';
                context.beginPath();
                if (typeof context.roundRect === 'function') context.roundRect(x, baseline - barHeight, barWidth, barHeight, barWidth / 2);
                else context.rect(x, baseline - barHeight, barWidth, barHeight);
                context.fill();
                context.globalAlpha = .7;
                context.fillRect(x, baseline + (4 * ratio), barWidth, Math.min(barHeight * .1, 7 * ratio));
                context.globalAlpha = 1;
            }
        } else if (state.vizMode === 'aurora') {
            const colors = [
                ['rgba(34,211,197,.06)', 'rgba(34,211,197,.42)'],
                ['rgba(67,140,244,.05)', 'rgba(91,110,246,.36)'],
                ['rgba(168,85,247,.04)', 'rgba(217,70,239,.32)'],
            ];
            colors.forEach((palette, layer) => {
                const fill = context.createLinearGradient(0, height * .1, 0, height);
                fill.addColorStop(0, palette[1]);
                fill.addColorStop(1, palette[0]);
                context.beginPath();
                const points = 180;
                for (let index = 0; index < points; index += 1) {
                    const sampleIndex = Math.floor((index / (points - 1)) * (timeValues.length - 1));
                    const sample = (timeValues[sampleIndex] - 128) / 128;
                    const x = (index / (points - 1)) * width;
                    const motion = Math.sin((index * .075) + (Date.now() / (760 + (layer * 170))) + layer) * height * .035;
                    const y = (height * (.33 + (layer * .13))) + (sample * height * (.19 - (layer * .025))) + motion;
                    if (!index) context.moveTo(x, y); else context.lineTo(x, y);
                }
                context.lineTo(width, height * .92);
                context.lineTo(0, height * .92);
                context.closePath();
                context.fillStyle = fill;
                context.globalAlpha = .76;
                context.fill();
                context.globalAlpha = .85;
                context.strokeStyle = layer === 0 ? '#35d6d0' : (layer === 1 ? '#5d7df2' : '#c04de7');
                context.lineWidth = (1.5 - (layer * .2)) * ratio;
                context.shadowBlur = 10 * ratio;
                context.shadowColor = context.strokeStyle;
                context.stroke();
            });
            context.globalAlpha = 1;
            context.shadowBlur = 0;
        } else if (state.vizMode === 'radial') {
            const centerX = width * .45;
            const centerY = height * .5;
            const baseRadius = Math.min(width, height) * .2;
            const spokes = 112;
            context.lineCap = 'round';
            for (let index = 0; index < spokes; index += 1) {
                const angle = ((Math.PI * 2) * index / spokes) - (Math.PI / 2);
                const frequencyIndex = Math.min(frequencies.length - 1, Math.floor(((index / spokes) ** 1.4) * Math.min(frequencies.length - 1, 210)));
                const live = frequencies[frequencyIndex] / 255;
                const idle = .12 + (Math.abs(Math.sin((index * .37) + (Date.now() / 460))) * .2);
                const energy = state.audio.paused ? idle : Math.max(idle * .45, live);
                const inner = baseRadius - (energy * 5 * ratio);
                const outer = baseRadius + (energy * height * .23);
                const hue = 178 + ((index / spokes) * 122);
                context.strokeStyle = `hsla(${hue} 88% 64% / ${.42 + (energy * .5)})`;
                context.lineWidth = Math.max(1.2 * ratio, 2.2 * ratio * energy);
                context.shadowBlur = energy > .46 ? 10 * ratio : 3 * ratio;
                context.shadowColor = `hsla(${hue} 88% 62% / .48)`;
                context.beginPath();
                context.moveTo(centerX + (Math.cos(angle) * inner), centerY + (Math.sin(angle) * inner));
                context.lineTo(centerX + (Math.cos(angle) * outer), centerY + (Math.sin(angle) * outer));
                context.stroke();
            }
            context.shadowBlur = 18 * ratio;
            context.strokeStyle = 'rgba(129,108,255,.48)';
            context.lineWidth = 1.2 * ratio;
            context.beginPath();
            context.arc(centerX, centerY, baseRadius, 0, Math.PI * 2);
            context.stroke();
            context.shadowBlur = 0;
        } else if (state.vizMode === 'particles') {
            const particles = 105;
            const now = Date.now() / 1000;
            for (let index = 0; index < particles; index += 1) {
                const frequencyIndex = Math.min(frequencies.length - 1, Math.floor(((index / particles) ** 1.65) * Math.min(frequencies.length - 1, 210)));
                const live = frequencies[frequencyIndex] / 255;
                const idle = .12 + (Math.abs(Math.sin((index * .73) + now)) * .16);
                const energy = state.audio.paused ? idle : Math.max(idle * .5, live);
                const lane = index % 7;
                const x = ((index * 83.7) + (now * (8 + lane))) % (width + (20 * ratio));
                const waveY = Math.sin((index * .42) + (now * (1.2 + (lane * .08)))) * height * (.12 + (energy * .2));
                const y = (height * (.18 + ((lane / 6) * .64))) + waveY;
                const radius = (1.1 + (energy * 3.1)) * ratio;
                const hue = 178 + ((index / particles) * 120);
                context.fillStyle = `hsla(${hue} 92% 66% / ${.28 + (energy * .7)})`;
                context.shadowBlur = (4 + (energy * 11)) * ratio;
                context.shadowColor = `hsla(${hue} 90% 62% / .7)`;
                context.beginPath();
                context.arc(x, y, radius, 0, Math.PI * 2);
                context.fill();
            }
            context.shadowBlur = 0;
        } else if (state.vizMode === 'laser') {
            const beams = 7;
            context.lineCap = 'round';
            context.lineJoin = 'round';
            for (let beam = 0; beam < beams; beam += 1) {
                const hue = 182 + ((beam / (beams - 1)) * 112);
                const vertical = height * (.2 + ((beam / (beams - 1)) * .6));
                context.beginPath();
                const points = 140;
                for (let index = 0; index < points; index += 1) {
                    const sampleIndex = Math.floor((index / (points - 1)) * (timeValues.length - 1));
                    const sample = Math.abs((timeValues[sampleIndex] - 128) / 128);
                    const x = (index / (points - 1)) * width;
                    const flicker = Math.sin((index * .19) + (Date.now() / (270 + (beam * 24)))) * height * .012;
                    const y = vertical + flicker + ((beam % 2 ? 1 : -1) * sample * height * .11);
                    if (!index) context.moveTo(x, y); else context.lineTo(x, y);
                }
                context.strokeStyle = `hsla(${hue} 94% 64% / ${.28 + (beam * .07)})`;
                context.lineWidth = (1 + ((beams - beam) * .12)) * ratio;
                context.shadowBlur = 9 * ratio;
                context.shadowColor = `hsla(${hue} 90% 60% / .58)`;
                context.stroke();
            }
            context.shadowBlur = 0;
        } else if (state.vizMode === 'ribbons') {
            const now = Date.now() / 1000;
            context.save();
            context.globalCompositeOperation = 'lighter';
            context.lineCap = 'round';
            context.lineJoin = 'round';
            for (let layer = 0; layer < 6; layer += 1) {
                const hue = 178 + (layer * 22);
                const baseline = height * (.23 + (layer * .105));
                context.beginPath();
                const points = 170;
                for (let index = 0; index < points; index += 1) {
                    const progress = index / (points - 1);
                    const sampleIndex = Math.min(timeValues.length - 1, Math.floor(progress * timeValues.length));
                    const frequencyIndex = Math.min(frequencies.length - 1, Math.floor((progress ** 1.55) * Math.min(frequencies.length - 1, 210)));
                    const sample = (timeValues[sampleIndex] - 128) / 128;
                    const live = frequencies[frequencyIndex] / 255;
                    const idle = .18 + (Math.abs(Math.sin((index * .09) + now + layer)) * .16);
                    const energy = state.audio.paused ? idle : Math.max(idle * .55, live);
                    const x = progress * width;
                    const drift = Math.sin((progress * Math.PI * (3.2 + (layer * .16))) + (now * (.55 + (layer * .04))) + layer) * height * .055;
                    const y = baseline + drift + (sample * height * (.075 + (energy * .075)));
                    if (!index) context.moveTo(x, y); else context.lineTo(x, y);
                }
                context.strokeStyle = `hsla(${hue} 92% 66% / ${.28 + (layer * .07)})`;
                context.lineWidth = (1.2 + ((5 - layer) * .18)) * ratio;
                context.shadowBlur = (7 + (layer * 1.4)) * ratio;
                context.shadowColor = `hsla(${hue} 92% 62% / .56)`;
                context.stroke();
            }
            context.restore();
        } else if (state.vizMode === 'rain') {
            const drops = 72;
            const now = Date.now() / 1000;
            context.save();
            context.globalCompositeOperation = 'lighter';
            context.lineCap = 'round';
            for (let index = 0; index < drops; index += 1) {
                const progress = index / (drops - 1);
                const frequencyIndex = Math.min(frequencies.length - 1, Math.floor((progress ** 1.65) * Math.min(frequencies.length - 1, 220)));
                const live = frequencies[frequencyIndex] / 255;
                const idle = .12 + (Math.abs(Math.sin((index * .61) + now)) * .14);
                const energy = state.audio.paused ? idle : Math.max(idle * .45, live);
                const laneOffset = ((index * 47) % drops) / drops;
                const x = (laneOffset * width) + (Math.sin((index * .73) + (now * .3)) * 6 * ratio);
                const speed = 28 + ((index % 9) * 5) + (energy * 72);
                const y = (((index * 31 * ratio) + (now * speed * ratio)) % (height + (70 * ratio))) - (35 * ratio);
                const length = (8 + (energy * 42)) * ratio;
                const hue = 178 + (progress * 118);
                const trail = context.createLinearGradient(0, y - length, 0, y + (3 * ratio));
                trail.addColorStop(0, `hsla(${hue} 92% 62% / 0)`);
                trail.addColorStop(1, `hsla(${hue} 94% 68% / ${.4 + (energy * .55)})`);
                context.strokeStyle = trail;
                context.lineWidth = (1 + (energy * 1.8)) * ratio;
                context.shadowBlur = (4 + (energy * 9)) * ratio;
                context.shadowColor = `hsla(${hue} 92% 62% / .7)`;
                context.beginPath();
                context.moveTo(x, y - length);
                context.lineTo(x, y);
                context.stroke();
                context.fillStyle = `hsla(${hue} 98% 74% / ${.48 + (energy * .5)})`;
                context.beginPath();
                context.arc(x, y, (1 + (energy * 1.5)) * ratio, 0, Math.PI * 2);
                context.fill();
            }
            context.restore();
        } else {
            const bars = 154;
            const gap = 1.6 * ratio;
            const barWidth = Math.max(1.7 * ratio, (width - ((bars - 1) * gap)) / bars);
            for (let index = 0; index < bars; index += 1) {
                const start = Math.floor((index / bars) * timeValues.length);
                const end = Math.max(start + 1, Math.floor(((index + 1) / bars) * timeValues.length));
                let localPeak = 0;
                for (let sample = start; sample < end; sample += 1) localPeak = Math.max(localPeak, Math.abs(timeValues[sample] - 128) / 128);
                const frequencyIndex = Math.min(frequencies.length - 1, Math.floor(((index / (bars - 1)) ** 2) * Math.min(frequencies.length - 1, 180)));
                const spectral = frequencies[frequencyIndex] / 255;
                const live = Math.max(localPeak * .9, spectral * .72);
                const broadWave = Math.abs(Math.sin((index * .137) + .72));
                const detailWave = Math.abs(Math.sin((index * .47) + 1.65));
                const microWave = Math.abs(Math.cos((index * .83) + .4));
                const directionEnvelope = .3 + ((1 - (index / (bars - 1))) * .7);
                const idle = Math.min(.9, (.12 + (broadWave * .43) + (detailWave * .19) + (microWave * .08)) * directionEnvelope);
                const amplitude = state.audio.paused ? idle : Math.max(idle * .42, live);
                const organicShape = .82 + ((Math.sin((index / bars) * Math.PI) ** 1.35) * .18);
                const barHeight = Math.max(7 * ratio, amplitude * organicShape * height * .82);
                const x = index * (barWidth + gap);
                const centerY = (height - barHeight) / 2;
                const isPlayed = (index / bars) <= played;
                context.fillStyle = isPlayed ? playedGradient : idleGradient;
                context.shadowBlur = isPlayed ? 13 * ratio : 0;
                context.shadowColor = 'rgba(77,116,244,.4)';
                context.beginPath();
                if (typeof context.roundRect === 'function') context.roundRect(x, centerY, barWidth, barHeight, barWidth / 2);
                else context.rect(x, centerY, barWidth, barHeight);
                context.fill();
            }
        }
        state.animationFrame = requestAnimationFrame(() => this.drawAudioVisualizer(state));
    },

    destroyAudioPreview() {
        const state = this.audioPreviewState;
        if (!state) return;
        if (state.animationFrame) cancelAnimationFrame(state.animationFrame);
        try { state.audio.pause(); } catch {}
        try { state.source?.disconnect(); } catch {}
        state.filters?.forEach((filter) => { try { filter.disconnect(); } catch {} });
        try { state.compressor?.disconnect(); } catch {}
        try { state.panner?.disconnect(); } catch {}
        ['bassBoost', 'warmth', 'toneFilter', 'dryGain', 'convolver', 'reverbGain', 'delay', 'echoGain', 'echoFeedback'].forEach((node) => {
            try { state[node]?.disconnect(); } catch {}
        });
        try { state.analyser?.disconnect(); } catch {}
        try { state.context?.close(); } catch {}
        this.audioPreviewState = null;
    },

};
