function validateStudentForm() {
  const name = document.querySelector('input[name="student_name"]').value.trim();
  const roll = document.querySelector('input[name="roll_number"]').value.trim();
  const section = document.querySelector('input[name="section"]').value.trim();

  if (!/^[A-Za-z ]+$/.test(name)) {
    alert("Name must contain only letters and spaces.");
    return false;
  }

  if (!/^\d+[A-Za-z]?$/.test(roll)) {
  alert("Roll number must start with digits and may optionally end with a single letter (e.g., 10A).");
  return false;
}


  if (!/^[A-Za-z]$/.test(section)) {
    alert("Section must be a single letter (e.g., A, B).");
    return false;
  }

  return true;
}
