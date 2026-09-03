(function () {
    "use strict";

    var audioContext = null;

    function getAudioContext() {
        var AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) {
            return null;
        }

        if (!audioContext) {
            audioContext = new AudioContext();
        }

        return audioContext;
    }

    function scheduleNote(audio, frequency, start, duration, wave, volume) {
        var now = audio.currentTime + start;
        var oscillator = audio.createOscillator();
        var gain = audio.createGain();

        oscillator.type = wave;
        oscillator.frequency.setValueAtTime(frequency, now);
        gain.gain.setValueAtTime(0.0001, now);
        gain.gain.exponentialRampToValueAtTime(volume, now + 0.01);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + duration);

        oscillator.connect(gain);
        gain.connect(audio.destination);
        oscillator.start(now);
        oscillator.stop(now + duration + 0.02);
    }

    function playNotes(notes) {
        var audio = getAudioContext();
        if (!audio) {
            return;
        }

        var play = function () {
            notes.forEach(function (note) {
                scheduleNote(audio, note.frequency, note.start, note.duration, note.wave, note.volume);
            });
        };

        if (audio.state === "suspended") {
            audio.resume().then(play).catch(function () { });
        } else {
            play();
        }
    }

    var sounds = {
        padrao: function () {
            playNotes([
                { frequency: 523.25, start: 0, duration: 0.16, wave: "sine", volume: 0.08 },
                { frequency: 659.25, start: 0.13, duration: 0.22, wave: "sine", volume: 0.08 }
            ]);
        },
        confirmar: function () {
            playNotes([
                { frequency: 523.25, start: 0, duration: 0.16, wave: "sine", volume: 0.07 },
                { frequency: 659.25, start: 0.12, duration: 0.16, wave: "sine", volume: 0.07 },
                { frequency: 783.99, start: 0.24, duration: 0.3, wave: "sine", volume: 0.08 }
            ]);
        },
        alerta: function () {
            playNotes([
                { frequency: 196, start: 0, duration: 0.24, wave: "triangle", volume: 0.1 }
            ]);
        }
    };

    function getElementText(element) {
        return ((element.getAttribute("data-sound") || "") + " " + (element.className || "") + " " + (element.textContent || ""))
            .toLocaleLowerCase("pt-BR");
    }

    function soundFor(element) {
        var text = getElementText(element);

        if (/confirmar|finalizar|concluir|aprovar/.test(text)) {
            return "confirmar";
        }
        if (/excluir|remover|sair|cancelar|alerta|perdido/.test(text)) {
            return "alerta";
        }
        return "padrao";
    }

    var lastElement = null;
    var lastTime = 0;

    function playFor(element) {
        var now = Date.now();
        if (element === lastElement && now - lastTime < 400) {
            return;
        }

        lastElement = element;
        lastTime = now;
        sounds[soundFor(element)]();
    }

    function getInteractiveElement(target) {
        if (!target || !target.closest) {
            return null;
        }

        return target.closest("button, a.btn, input[type='submit'], input[type='button'], [role='button']");
    }

    document.addEventListener("pointerdown", function (event) {
        var element = getInteractiveElement(event.target);
        if (element) {
            playFor(element);
        }
    }, true);

    document.addEventListener("click", function (event) {
        var element = getInteractiveElement(event.target);
        if (element) {
            playFor(element);
        }
    }, true);

    window.PetfinderClickSounds = sounds;
})();
