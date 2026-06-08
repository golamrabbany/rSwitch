// Stereo live-listen player. Receives {side, samples:Float32Array} via port.
// side 0 = left (caller), 1 = right (callee). Input is 8kHz signed-linear (SLIN,
// 320-byte/20ms frames). Each side is an INDEPENDENT jitter-buffered channel
// (linear-interpolated up to the AudioContext rate) so the two legs — which can
// arrive at different times/rates (e.g. g729 caller vs alaw callee) — never
// starve each other. A channel holds an ~120ms cushion before (re)starting so a
// short network gap is one clean pause instead of continuous breakup.

const PREBUFFER = 8000 * 0.12; // 120ms of 8kHz input samples

class Channel {
    constructor(outStep) {
        this.buf = new Float32Array(8192); // ~1s at 8kHz; grows if needed
        this.len = 0;                      // valid samples in buf
        this.pos = 0.0;                    // fractional read position
        this.step = outStep;               // input samples consumed per output sample
        this.armed = false;                // false = filling the cushion
    }

    push(samples) {
        const start = Math.floor(this.pos);
        const live = this.len - start;     // still-unread samples
        if (live + samples.length > this.buf.length) {
            let cap = this.buf.length;
            while (live + samples.length > cap) cap *= 2;
            const nb = new Float32Array(cap);
            nb.set(this.buf.subarray(start, this.len), 0);
            this.buf = nb;
            this.len = live;
            this.pos -= start;
        } else if (start > 4096) {
            this.buf.copyWithin(0, start, this.len); // bound memory
            this.len -= start;
            this.pos -= start;
        }
        this.buf.set(samples, this.len);
        this.len += samples.length;
    }

    read() {
        // Hold output until this channel alone has filled its cushion.
        if (!this.armed) {
            if (this.len - this.pos < PREBUFFER) return 0;
            this.armed = true;
        }
        const i = Math.floor(this.pos);
        if (i + 1 >= this.len) {           // underflow -> re-fill cushion
            this.armed = false;
            return 0;
        }
        const frac = this.pos - i;
        const s = this.buf[i] * (1 - frac) + this.buf[i + 1] * frac; // linear interp
        this.pos += this.step;
        return s;
    }
}

class ListenProcessor extends AudioWorkletProcessor {
    constructor() {
        super();
        const step = 8000 / sampleRate;    // input 8kHz -> context output rate
        this.L = new Channel(step);
        this.R = new Channel(step);
        this.frame = 0;
        this.port.onmessage = (e) => {
            const { side, samples } = e.data;
            (side === 0 ? this.L : this.R).push(samples);
        };
    }

    process(inputs, outputs) {
        const out = outputs[0];
        const outL = out[0];
        const outR = out.length > 1 ? out[1] : out[0];
        let sumL = 0, sumR = 0;
        for (let i = 0; i < outL.length; i++) {
            const l = this.L.read();
            const r = this.R.read();
            outL[i] = l;
            outR[i] = r;
            sumL += l * l;
            sumR += r * r;
        }
        if ((++this.frame % 10) === 0) {
            this.port.postMessage({
                rmsL: Math.sqrt(sumL / outL.length),
                rmsR: Math.sqrt(sumR / outR.length),
            });
        }
        return true;
    }
}

registerProcessor('listen-processor', ListenProcessor);
