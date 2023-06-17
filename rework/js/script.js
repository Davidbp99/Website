window.addEventListener('scroll', function() {
  var discordButtonContainer = document.getElementById('discordButtonContainer');
  if (discordButtonContainer) {
    var scrollY = window.scrollY || window.pageYOffset;
    var windowHeight = window.innerHeight;
    var documentHeight = document.documentElement.scrollHeight;
    var bottomOffset = documentHeight - (scrollY + windowHeight);

    if (bottomOffset > 100) {
      discordButtonContainer.style.bottom = '20px';
    } else {
      discordButtonContainer.style.bottom = '120px';
    }
  }
});
