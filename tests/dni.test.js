const test = require("node:test");
const assert = require("node:assert/strict");
const Dni = require("../sites/dni-validator/js/dni.js");

test("letterFor follows the official table", () => {
    assert.equal(Dni.letterFor(12345678), "Z");
    assert.equal(Dni.letterFor(0), "T");
    assert.equal(Dni.letterFor(22), "E");
    assert.equal(Dni.letterFor(23), "T");
    assert.equal(Dni.letterFor(99999999), "R");
});

test("every remainder maps to a different letter", () => {
    const letters = new Set();
    for (let n = 0; n < 23; n++) {
        letters.add(Dni.letterFor(n));
    }
    assert.equal(letters.size, 23);
});

test("letterFor rejects numbers out of range", () => {
    for (const bad of [-1, 100000000, 1.5, Number.NaN]) {
        assert.throws(() => Dni.letterFor(bad), RangeError);
    }
});

test("check works out the letter of a DNI", () => {
    assert.deepEqual(Dni.check("12345678"), { ok: true, letter: "Z", id: "12345678Z", number: 12345678, remainder: 14 });
});

test("check pads short numbers to eight digits", () => {
    assert.deepEqual(Dni.check("1"), { ok: true, letter: "R", id: "00000001R", number: 1, remainder: 1 });
});

test("check accepts a complete DNI with the right letter, in any case and with separators", () => {
    for (const text of ["12345678Z", "12345678z", " 12.345.678-Z "]) {
        assert.equal(Dni.check(text).ok, true, text);
    }
});

test("check reports a wrong letter and says which one is right", () => {
    const result = Dni.check("12345678A");
    assert.equal(result.ok, false);
    assert.match(result.error, /should be Z/);
});

test("check supports NIEs", () => {
    assert.deepEqual(Dni.check("X1234567"), { ok: true, letter: "L", id: "X1234567L", number: 1234567, remainder: 19 });
    assert.equal(Dni.check("Y1234567").number, 11234567);
    assert.equal(Dni.check("Y1234567X").ok, true);
    assert.equal(Dni.check("Z1234567R").ok, true);
    assert.equal(Dni.check("X1234567A").ok, false);
});

test("check rejects a NIE with too many digits", () => {
    assert.equal(Dni.check("X12345678").ok, false);
});

test("check rejects empty and malformed input", () => {
    for (const bad of ["", "   ", null, undefined, "abc", "1234 5678 9", "123456789", "12345678ZZ", "1e5", "12345678Ñ", "A1234567"]) {
        const result = Dni.check(bad);
        assert.equal(result.ok, false, String(bad));
        assert.ok(result.error.length > 0);
    }
});

test("the letter table has the 23 official letters, each once", () => {
    assert.equal(Dni.LETTERS.length, 23);
    assert.equal(new Set(Dni.LETTERS).size, 23);
    for (const unused of "IÑOU") {
        assert.ok(!Dni.LETTERS.includes(unused), unused);
    }
});
