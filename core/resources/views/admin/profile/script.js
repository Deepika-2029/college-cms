function pwStrength(pw) {
    let s = 0;
    if (pw.length >= 10)              s++;
    if (/[A-Z]/.test(pw))             s++;
    if (/[0-9]/.test(pw))             s++;
    if (/[^a-zA-Z0-9]/.test(pw))      s++;
    return s;
}
