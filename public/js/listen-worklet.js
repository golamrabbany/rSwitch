// Stereo live-listen player. Receives {side, samples:Float32Array} via port.
// side 0 = left (caller), 1 = right (callee). Plays at the context sample rate;
// input is 8kHz so we upsample by nearest-neighbour (adequate for voice).
class ListenProcessor extends AudioWorkletProcessor {
    constructor() {
        super();
        this.left = [];
        this.right = [];
        this.inRate = 8000;
        this.ratio = this.inRate / sampleRate; // input samples consumed per output sample
        this.posL = 0;
        this.posR = 0;
        this.frameCount = 0;
        this.port.onmessage = (e) => {
            const { side, samples } = e.data;
            if (side === 0) this.left.push(...samples);
            else this.right.push(...samples);
        };
    }

    _pull(buf, posKey) {
        // Nearest-neighbour resample from 8kHz to sampleRate.
        if (buf.length === 0) return 0;
        const idx = Math.floor(this[posKey]);
        const s = buf[idx] || 0;
        this[posKey] += this.ratio;
        if (this[posKey] >= buf.length) {
            buf.splice(0, Math.floor(this[posKey]));
            this[posKey] -= Math.floor(this[posKey]);
        }
        return s;
    }

    process(inputs, outputs) {
        const out = outputs[0];
        const outL = out[0];
        const outR = out.length > 1 ? out[1] : out[0];
        let sumL = 0, sumR = 0;
        for (let i = 0; i < outL.length; i++) {
            const l = this._pull(this.left, 'posL');
            const r = this._pull(this.right, 'posR');
            outL[i] = l;
            outR[i] = r;
            sumL += l * l;
            sumR += r * r;
        }
        // Emit RMS roughly every ~10 render quanta (~26ms) for the visualizer.
        this.frameCount++;
        if (this.frameCount % 10 === 0) {
            this.port.postMessage({
                rmsL: Math.sqrt(sumL / outL.length),
                rmsR: Math.sqrt(sumR / outR.length),
            });
        }
        return true;
    }
}

registerProcessor('listen-processor', ListenProcessor);
