#!/usr/bin/env python3
"""
Generate default sound effects for the prize wheel using Python's wave module.
Creates WAV files for: spin start, victory, try-again, and tick sounds.
"""

import wave
import struct
import math
import os

SAMPLE_RATE = 44100
OUTPUT_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'static', 'sounds')


def generate_samples(duration_s, generator_func):
    """Generate audio samples using a generator function."""
    num_samples = int(SAMPLE_RATE * duration_s)
    samples = []
    for i in range(num_samples):
        t = i / SAMPLE_RATE
        value = generator_func(t, duration_s)
        value = max(-1.0, min(1.0, value))
        samples.append(value)
    return samples


def write_wav(filename, samples):
    """Write samples to a WAV file."""
    filepath = os.path.join(OUTPUT_DIR, filename)
    with wave.open(filepath, 'w') as wav_file:
        wav_file.setnchannels(1)
        wav_file.setsampwidth(2)  # 16-bit
        wav_file.setframerate(SAMPLE_RATE)
        for sample in samples:
            packed = struct.pack('<h', int(sample * 32767))
            wav_file.writeframes(packed)
    print(f"  Created: {filepath} ({len(samples)} samples, {len(samples)/SAMPLE_RATE:.2f}s)")


def envelope(t, attack, decay, sustain_level, release, duration):
    """ADSR envelope."""
    sustain_end = duration - release
    if t < attack:
        return t / attack
    elif t < attack + decay:
        return 1.0 - (1.0 - sustain_level) * ((t - attack) / decay)
    elif t < sustain_end:
        return sustain_level
    elif t < duration:
        return sustain_level * (1.0 - (t - sustain_end) / release)
    return 0.0


def generate_spin_sound():
    """Generate a whoosh/riser sound for spin start."""
    duration = 1.5

    def gen(t, dur):
        env = envelope(t, 0.05, 0.2, 0.7, 0.5, dur)
        # Rising frequency sweep
        freq = 200 + 800 * (t / dur)
        # Mix of sine and noise-like content
        base = math.sin(2 * math.pi * freq * t) * 0.4
        # Add harmonics for richness
        harm2 = math.sin(2 * math.pi * freq * 2.01 * t) * 0.2
        harm3 = math.sin(2 * math.pi * freq * 3.02 * t) * 0.1
        # Pseudo-noise using multiple detuned oscillators
        noise = (math.sin(2 * math.pi * 1337 * t + math.sin(t * 7919)) * 0.15 +
                 math.sin(2 * math.pi * 2671 * t + math.sin(t * 3571)) * 0.1)
        return (base + harm2 + harm3 + noise) * env

    samples = generate_samples(duration, gen)
    write_wav('spin.wav', samples)


def generate_victory_sound():
    """Generate a triumphant fanfare for winning."""
    duration = 2.0

    def gen(t, dur):
        result = 0.0

        # Note sequence: C5 -> E5 -> G5 -> C6 (ascending arpeggio) then chord
        notes = [
            (0.0, 0.3, 523.25),   # C5
            (0.2, 0.3, 659.25),   # E5
            (0.4, 0.3, 783.99),   # G5
            (0.6, 1.2, 1046.50),  # C6 (held)
        ]

        for start, note_dur, freq in notes:
            if start <= t < start + note_dur:
                local_t = t - start
                env = envelope(local_t, 0.02, 0.05, 0.8, 0.15, note_dur)
                tone = math.sin(2 * math.pi * freq * t) * 0.3
                tone += math.sin(2 * math.pi * freq * 2 * t) * 0.15
                tone += math.sin(2 * math.pi * freq * 3 * t) * 0.08
                result += tone * env

        # Final chord (C major) starting at 0.8s
        if t >= 0.8:
            chord_t = t - 0.8
            chord_dur = dur - 0.8
            chord_env = envelope(chord_t, 0.05, 0.3, 0.6, 0.5, chord_dur)
            chord_freqs = [523.25, 659.25, 783.99, 1046.50]  # C major with octave
            for freq in chord_freqs:
                result += math.sin(2 * math.pi * freq * t) * 0.12 * chord_env
                result += math.sin(2 * math.pi * freq * 2 * t) * 0.05 * chord_env

        # Add shimmer/sparkle effect
        if t > 0.6:
            shimmer_t = t - 0.6
            shimmer_env = envelope(shimmer_t, 0.1, 0.2, 0.3, 0.5, dur - 0.6)
            shimmer = (math.sin(2 * math.pi * 2093 * t) * 0.05 +
                       math.sin(2 * math.pi * 2637 * t) * 0.04 +
                       math.sin(2 * math.pi * 3136 * t) * 0.03)
            result += shimmer * shimmer_env

        return result

    samples = generate_samples(duration, gen)
    write_wav('victory.wav', samples)


def generate_tryagain_sound():
    """Generate a descending 'wah wah' sound for losing."""
    duration = 1.5

    def gen(t, dur):
        result = 0.0

        # Descending notes: G4 -> E4 -> C4 -> low C3
        notes = [
            (0.0, 0.35, 392.00, 349.23),   # G4 bending to F4
            (0.35, 0.35, 329.63, 293.66),   # E4 bending to D4
            (0.7, 0.8, 261.63, 200.00),     # C4 bending down
        ]

        for start, note_dur, freq_start, freq_end in notes:
            if start <= t < start + note_dur:
                local_t = t - start
                progress = local_t / note_dur
                freq = freq_start + (freq_end - freq_start) * progress
                env = envelope(local_t, 0.03, 0.1, 0.7, 0.2, note_dur)

                # Brass-like tone
                tone = math.sin(2 * math.pi * freq * t) * 0.35
                tone += math.sin(2 * math.pi * freq * 2 * t) * 0.2
                tone += math.sin(2 * math.pi * freq * 3 * t) * 0.12
                tone += math.sin(2 * math.pi * freq * 4 * t) * 0.06

                # Wah effect (low-pass filter simulation via amplitude modulation)
                wah = 0.5 + 0.5 * math.sin(2 * math.pi * 3 * local_t)
                result += tone * env * (0.5 + 0.5 * wah)

        return result

    samples = generate_samples(duration, gen)
    write_wav('try-again.wav', samples)


def generate_tick_sound():
    """Generate a short click/tick for segment passing."""
    duration = 0.05

    def gen(t, dur):
        env = envelope(t, 0.001, 0.01, 0.3, 0.02, dur)
        # Sharp click using multiple frequencies
        click = (math.sin(2 * math.pi * 1200 * t) * 0.4 +
                 math.sin(2 * math.pi * 2400 * t) * 0.3 +
                 math.sin(2 * math.pi * 800 * t) * 0.2 +
                 math.sin(2 * math.pi * 4800 * t) * 0.1)
        return click * env

    samples = generate_samples(duration, gen)
    write_wav('tick.wav', samples)


if __name__ == '__main__':
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    print("Generating default prize wheel sounds...")
    generate_spin_sound()
    generate_victory_sound()
    generate_tryagain_sound()
    generate_tick_sound()
    print("Done! All sounds generated.")
