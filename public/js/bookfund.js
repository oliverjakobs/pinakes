const formatter = new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});
  
document.querySelectorAll(".currency-input > input").forEach((input) => {
    input.addEventListener("input", (e) => {
        const raw = e.target.value.replace(/\D/g, "");
        e.target.value = formatter.format(Number(raw) / 100);
    })
});
  