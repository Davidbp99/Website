window.addEventListener("load", function () {
  // Hide the loading animation
  var loadingAnimation = document.getElementById("loading-animation");
  loadingAnimation.style.opacity = 0;

  // Show the website content
  var body = document.querySelector("body");
  body.classList.add("loaded");
});
