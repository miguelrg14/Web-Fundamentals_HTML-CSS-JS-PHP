// Spanish ID numbers: the DNI (8 digits + check letter) and the NIE for foreign residents
// (X, Y or Z + 7 digits + check letter). The letter is LETTERS[number mod 23]; for a NIE the
// leading X, Y or Z counts as the digit 0, 1 or 2.
//
// Plain script (no modules) so the page also works when opened straight from disk;
// Node loads it with require() for the tests.
const Dni = (() => {
    const LETTERS = "TRWAGMYFPDXBNJZSQVHLCKE";
    const NIE_PREFIXES = "XYZ";
    const PATTERN = /^([XYZ]?)(\d{1,8})([A-Z]?)$/;

    /** Check letter for a whole number between 0 and 99,999,999. */
    function letterFor(number) {
        if (!Number.isInteger(number) || number < 0 || number > 99_999_999) {
            throw new RangeError("The number must be an integer between 0 and 99999999.");
        }
        return LETTERS[number % 23];
    }

    /**
     * Reads what the user typed: "12345678" gives its letter, "12345678Z" also checks that the
     * letter is right, and "X1234567L" is a NIE. Spaces, dots and dashes are ignored.
     * Returns { ok: true, letter, id, number, remainder } or { ok: false, error }:
     * number is what the letter is worked out from, and remainder is number mod 23.
     */
    function check(input) {
        const text = String(input ?? "").toUpperCase().replace(/[\s.-]/g, "");
        if (text === "") {
            return { ok: false, error: "Type the numbers of your DNI or NIE." };
        }

        const match = PATTERN.exec(text);
        if (match === null) {
            return { ok: false, error: "Only digits are allowed, optionally with X, Y or Z at the start and the letter at the end." };
        }

        const [, prefix, digits, typedLetter] = match;
        if (prefix !== "" && digits.length > 7) {
            return { ok: false, error: "A NIE has a letter (X, Y or Z) followed by at most 7 digits." };
        }

        const number = prefix === ""
            ? Number(digits)
            : Number(`${NIE_PREFIXES.indexOf(prefix)}${digits.padStart(7, "0")}`);
        const letter = letterFor(number);
        const id = `${prefix}${digits.padStart(prefix === "" ? 8 : 7, "0")}${letter}`;

        if (typedLetter !== "" && typedLetter !== letter) {
            return { ok: false, error: `The letter ${typedLetter} is wrong: it should be ${letter} (${id}).` };
        }
        return { ok: true, letter, id, number, remainder: number % 23 };
    }

    return { LETTERS, letterFor, check };
})();

if (typeof module !== "undefined") {
    module.exports = Dni;
}
