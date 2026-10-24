import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Assessment wizard state.
 *
 * One Likert item per screen with keyboard shortcuts (1-5 to answer,
 * Enter/ArrowRight to advance, ArrowLeft to go back). All answers are kept
 * in hidden inputs inside a single form so the existing server-side
 * validation and throttling stay untouched.
 *
 * Guards:
 * - An advance debounce prevents click + Enter double-firing from skipping
 *   questions (a focused option button re-clicks on Enter).
 * - Blocked actions surface a hint message instead of failing silently.
 * - Progress is mirrored into sessionStorage so a refresh resumes instead
 *   of losing every answer.
 */
window.assessmentWizard = (config) => ({
    total: config.total,
    questions: config.questions,
    storageKey: config.storageKey || null,
    current: 0,
    answers: {},
    submitting: false,
    hint: '',
    hintTimer: null,
    lastAdvanceAt: 0,

    init() {
        const restored = this.restoreProgress();

        if (!restored) {
            this.jumpToFirstUnanswered();
        }

        window.addEventListener('keydown', (event) => {
            if (this.submitting || event.metaKey || event.ctrlKey || event.altKey) {
                return;
            }

            const target = event.target;

            if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) && target.type !== 'hidden') {
                return;
            }

            if (
                ['1', '2', '3', '4', '5'].includes(event.key)
                && Number(event.key) <= (this.questions[this.current]?.options?.length ?? 0)
            ) {
                this.select(this.questions[this.current].id, Number(event.key));
            } else if (event.key === 'Enter' || event.key === 'ArrowRight') {
                event.preventDefault();
                this.next();
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                this.prev();
            }
        });

        window.addEventListener('beforeunload', () => this.persistProgress());
    },

    get progress() {
        return Math.round(((this.current + 1) / this.total) * 100);
    },

    answered(id) {
        return this.answers[id] !== undefined && this.answers[id] !== '';
    },

    answeredCount() {
        return this.questions.filter((question) => this.answered(question.id)).length;
    },

    select(id, value) {
        this.answers[id] = value;
        this.clearHint();
        this.persistProgress();

        // Auto-advance only when the answered item is on screen; the
        // debounce inside advance() absorbs double-fire events.
        const index = this.questions.findIndex((question) => question.id === id);

        if (index === this.current) {
            this.advance();
        }
    },

    canAdvance() {
        // Swallow duplicate triggers (Enter re-activating a focused option
        // button, impatient double clicks) within a short window.
        return Date.now() - this.lastAdvanceAt >= 250;
    },

    advance() {
        if (!this.canAdvance()) {
            return;
        }

        this.lastAdvanceAt = Date.now();

        if (this.current < this.total - 1) {
            this.current++;
            this.persistProgress();

            return;
        }

        this.finish();
    },

    next() {
        const question = this.questions[this.current];

        if (question && !this.answered(question.id)) {
            this.flashHint('Please choose an answer before continuing.');
            return;
        }

        this.advance();
    },

    prev() {
        if (this.current > 0) {
            this.current--;
            this.clearHint();
            this.persistProgress();
        }
    },

    jumpToFirstUnanswered() {
        const index = this.questions.findIndex((question) => !this.answered(question.id));

        if (index !== -1) {
            this.current = index;
        }

        return index === -1;
    },

    finish() {
        if (!this.jumpToFirstUnanswered()) {
            const missing = this.total - this.answeredCount();

            this.flashHint(
                missing+' question'+(missing > 1 ? 's' : '')+' still need'+(missing > 1 ? '' : 's')+
                ' an answer — jumped to the first one.'
            );

            return;
        }

        for (const question of this.questions) {
            const input = this.$refs.form.elements.namedItem('question_'+question.id);

            if (!input || !this.answered(question.id)) {
                this.flashHint('An answer could not be prepared for submission. Please review this item and try again.');
                return;
            }

            input.value = String(this.answers[question.id]);
        }

        this.submitting = true;

        this.$refs.form.submit();
    },

    flashHint(message) {
        this.hint = message;

        clearTimeout(this.hintTimer);
        this.hintTimer = setTimeout(() => this.clearHint(), 3000);
    },

    clearHint() {
        this.hint = '';
        clearTimeout(this.hintTimer);
    },

    persistProgress() {
        if (!this.storageKey || this.submitting) {
            return;
        }

        try {
            sessionStorage.setItem(this.storageKey, JSON.stringify({
                answers: this.answers,
                current: this.current,
            }));
        } catch (error) {
            // Quota/private-mode failures are non-fatal.
        }
    },

    restoreProgress() {
        if (!this.storageKey) {
            return false;
        }

        try {
            const raw = sessionStorage.getItem(this.storageKey);

            if (!raw) {
                return false;
            }

            const saved = JSON.parse(raw);
            const knownIds = new Set(this.questions.map((question) => question.id));
            const savedIds = Object.keys(saved.answers ?? {})
                .map(Number)
                .filter((id) => knownIds.has(id));

            // Partial progress is expected mid-assessment: restore whenever
            // at least one saved answer still maps to a live question.
            if (savedIds.length === 0) {
                sessionStorage.removeItem(this.storageKey);

                return false;
            }

            this.answers = saved.answers ?? {};

            if (Number.isInteger(saved.current) && saved.current >= 0 && saved.current < this.total) {
                this.current = saved.current;
            }

            return true;
        } catch (error) {
            // Corrupt or unavailable storage: start fresh.

            return false;
        }
    },
});

/**
 * Minimal dependency-free confetti burst used on fresh results.
 */
window.gabayConfetti = () => {
    const colors = ['#002045', '#fcb812', '#d6e3ff', '#ffdea7'];

    const canvas = document.createElement('canvas');

    canvas.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;pointer-events:none;z-index:9999';
    document.body.appendChild(canvas);

    const ctx = canvas.getContext('2d');

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const particles = Array.from({ length: 120 }, () => ({
        x: canvas.width / 2 + (Math.random() - 0.5) * 200,
        y: canvas.height * 0.35,
        vx: (Math.random() - 0.5) * 12,
        vy: Math.random() * -10 - 4,
        size: Math.random() * 7 + 4,
        color: colors[Math.floor(Math.random() * colors.length)],
        rotation: Math.random() * Math.PI,
        spin: (Math.random() - 0.5) * 0.3,
    }));

    let frame = 0;

    const tick = () => {
        frame++;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        particles.forEach((particle) => {
            particle.x += particle.vx;
            particle.y += particle.vy;
            particle.vy += 0.35;
            particle.rotation += particle.spin;

            ctx.save();
            ctx.translate(particle.x, particle.y);
            ctx.rotate(particle.rotation);
            ctx.fillStyle = particle.color;
            ctx.globalAlpha = Math.max(0, 1 - frame / 90);
            ctx.fillRect(-particle.size / 2, -particle.size / 2, particle.size, particle.size * 0.6);
            ctx.restore();
        });

        if (frame < 90) {
            requestAnimationFrame(tick);
        } else {
            canvas.remove();
        }
    };

    requestAnimationFrame(tick);
};

Alpine.start();
