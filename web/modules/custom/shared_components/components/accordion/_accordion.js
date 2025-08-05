document.addEventListener("DOMContentLoaded", function () {
  const accordionHeads = document.querySelectorAll(".accordion_head");

  accordionHeads.forEach(head => {
    head.addEventListener("click", function () {
      this.classList.remove("coll-back");

      const allBodies = document.querySelectorAll(".accordion_body");
      const allPlusMinus = document.querySelectorAll(".plusminus");
      const rmvClsElements = document.querySelectorAll(".rmv-cls");

      // Si algún acordeón está visible, ciérralo
      allBodies.forEach(body => {
        if (body.style.display === "block") {
          body.style.display = "none";
        }
      });

      // Resetear los símbolos y clases
      allPlusMinus.forEach(pm => pm.textContent = "+");
      this.classList.remove("coll-back");
      rmvClsElements.forEach(el => el.classList.remove("coll-back"));

      const nextBody = this.nextElementSibling;
      if (nextBody && nextBody.classList.contains("accordion_body")) {
        if (nextBody.style.display === "block") {
          nextBody.style.display = "none";
          this.querySelector(".plusminus").textContent = "+";
          this.classList.remove("coll-back");
        } else {
          nextBody.style.display = "block";
          const plusMinus = this.querySelector(".plusminus");
          plusMinus.textContent = "";
          const hr = document.createElement("hr");
          hr.className = "hr-clc";
          plusMinus.appendChild(hr);
          this.classList.toggle("coll-back");
          this.classList.add("rmv-cls");
        }
      }
    });
  });
});
