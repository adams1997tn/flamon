(function () {
  'use strict';

  const page = document.querySelector('.landing-two-page');
  if (!page) {
    return;
  }

  const heroCardsHost = page.querySelector('.hero-cards');
  if (heroCardsHost) {
    let cardsData = [];
    try {
      cardsData = JSON.parse(heroCardsHost.dataset.cards || '[]');
    } catch (error) {
      cardsData = [];
    }

    if (cardsData.length) {
      const populate = (cardEl, data) => {
        if (!cardEl || !data) {
          return;
        }
        const cover = cardEl.querySelector('[data-hero-cover]');
        const avatar = cardEl.querySelector('[data-hero-avatar]');
        const title = cardEl.querySelector('[data-hero-title]');
        const artist = cardEl.querySelector('[data-hero-artist]');
        const statOne = cardEl.querySelector('[data-hero-stat-one]');
        const statTwo = cardEl.querySelector('[data-hero-stat-two]');

        if (cover) {
          cover.src = data.cover || cover.dataset.placeholder || '';
          cover.alt = data.fullName || cover.alt;
        }
        if (avatar) {
          avatar.src = data.avatar || avatar.dataset.placeholder || '';
          avatar.alt = data.fullName || avatar.alt;
        }
        if (title) {
          title.textContent = data.fullName || '';
        }
        if (artist) {
          artist.textContent = data.username ? '@' + data.username : '';
        }
        if (statOne) {
          statOne.textContent = formatStat(data.statOneValue);
        }
        if (statTwo) {
          statTwo.textContent = formatStat(data.statTwoValue);
        }
      };

      const formatStat = (value) => {
        const num = Number(value);
        if (Number.isNaN(num)) {
          return value || '0';
        }
        if (num >= 1e6) {
          return (num / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
        }
        if (num >= 1e3) {
          return (num / 1e3).toFixed(1).replace(/\.0$/, '') + 'K';
        }
        return num.toString();
      };

      let order = [0, 1, 2];
      let nextIndex = 3 % cardsData.length;

      const render = () => {
        ['card--back', 'card--front', 'card--right'].forEach((cls, idx) => {
          populate(heroCardsHost.querySelector('.' + cls), cardsData[order[idx] % cardsData.length]);
        });
      };

      const rotate = () => {
        if (cardsData.length < 2) {
          return;
        }
        const back = heroCardsHost.querySelector('.card--back');
        const front = heroCardsHost.querySelector('.card--front');
        const right = heroCardsHost.querySelector('.card--right');

        back.classList.replace('card--back', 'card--right');
        front.classList.replace('card--front', 'card--back');
        right.classList.replace('card--right', 'card--front');

        order = [order[1], order[2], nextIndex % cardsData.length];
        nextIndex = (nextIndex + 1) % cardsData.length;
        render();
      };

      render();
      let heroInterval = window.setInterval(rotate, 4000);
      heroCardsHost.addEventListener('mouseenter', () => window.clearInterval(heroInterval));
      heroCardsHost.addEventListener('mouseleave', () => {
        heroInterval = window.setInterval(rotate, 4000);
      });
    }
  }

  const featurePanels = page.querySelectorAll('.feature-panel');
  if (featurePanels.length) {
    const order = [2, 3, 4, 0, 1];
    let currentIndex = 0;

    const activate = (idx) => {
      featurePanels.forEach((panel) => panel.classList.remove('active'));
      const target = featurePanels[order[idx]];
      if (target) {
        target.classList.add('active');
      }
    };

    activate(currentIndex);

    let intervalId = window.setInterval(() => {
      currentIndex = (currentIndex + 1) % order.length;
      activate(currentIndex);
    }, 8000);

    featurePanels.forEach((panel, idx) => {
      const handle = () => {
        const newIndex = order.indexOf(idx);
        if (newIndex !== -1) {
          currentIndex = newIndex;
          activate(currentIndex);
          window.clearInterval(intervalId);
          intervalId = window.setInterval(() => {
            currentIndex = (currentIndex + 1) % order.length;
            activate(currentIndex);
          }, 8000);
        }
      };
      panel.addEventListener('click', handle);
      panel.addEventListener('mouseenter', handle);
    });
  }

  const galleryScrollDuration = 155000;

  const initGalleryRow = (row) => {
    const track = row.querySelector('.cp-gallery-track');
    if (!track) {
      return;
    }
    const baseGroup = track.querySelector('.cp-gallery-group:not(.clone)');
    const cloneGroup = track.querySelector('.cp-gallery-group.clone');
    if (baseGroup && cloneGroup && !cloneGroup.children.length) {
      cloneGroup.innerHTML = baseGroup.innerHTML;
    }

    const direction = row.classList.contains('cp-gallery-right') ? -1 : 1;
    let loopDistance = 0;
    let virtualScroll = row.scrollLeft || 0;
    let dragStartX = 0;
    let startVirtualScroll = 0;
    let isDragging = false;
    let isPaused = false;
    let lastTimestamp = performance.now();
    let pointerId = null;

    const applyScroll = () => {
      if (!loopDistance) {
        return;
      }
      virtualScroll = ((virtualScroll % loopDistance) + loopDistance) % loopDistance;
      row.scrollLeft = virtualScroll;
    };

    const computeLoopDistance = () => {
      const groups = track.querySelectorAll('.cp-gallery-group');
      return groups.length ? track.scrollWidth / groups.length : 0;
    };

    const updateLoopDistance = () => {
      const previous = loopDistance;
      loopDistance = computeLoopDistance();
      if (!loopDistance) {
        return;
      }
      if (previous) {
        const progress = virtualScroll / previous;
        virtualScroll = progress * loopDistance;
      }
      applyScroll();
    };

    updateLoopDistance();

    const frame = (timestamp) => {
      const delta = timestamp - lastTimestamp;
      lastTimestamp = timestamp;
      if (!isPaused && loopDistance) {
        const pxPerMs = loopDistance / galleryScrollDuration;
        virtualScroll += direction * delta * pxPerMs;
        applyScroll();
      }
      window.requestAnimationFrame(frame);
    };
    window.requestAnimationFrame(frame);

    const handlePointerMove = (event) => {
      if (!isDragging) {
        return;
      }
      const deltaX = dragStartX - event.clientX;
      virtualScroll = startVirtualScroll + deltaX;
      applyScroll();
    };

    const stopDragging = () => {
      if (!isDragging) {
        return;
      }
      isDragging = false;
      row.classList.remove('is-dragging');
      track.classList.remove('is-dragging');
      isPaused = false;
      window.removeEventListener('pointermove', handlePointerMove);
      window.removeEventListener('pointerup', stopDragging);
      window.removeEventListener('pointercancel', stopDragging);
      if (pointerId !== null) {
        track.releasePointerCapture?.(pointerId);
        pointerId = null;
      }
    };

    track.addEventListener('pointerdown', (event) => {
      if (event.pointerType === 'mouse' && event.button !== 0) {
        return;
      }
      if (event.target.closest('.cp-gallery-card__button')) {
        return;
      }
      isDragging = true;
      isPaused = true;
      pointerId = event.pointerId;
      dragStartX = event.clientX;
      startVirtualScroll = virtualScroll;
      row.classList.add('is-dragging');
      track.classList.add('is-dragging');
      event.preventDefault();
      track.setPointerCapture?.(event.pointerId);
      window.addEventListener('pointermove', handlePointerMove);
      window.addEventListener('pointerup', stopDragging);
      window.addEventListener('pointercancel', stopDragging);
    });

    track.addEventListener('mouseenter', () => {
      isPaused = true;
    });

    track.addEventListener('mouseleave', () => {
      if (!isDragging) {
        isPaused = false;
      }
    });

    const resizeObserver = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(updateLoopDistance) : null;
    resizeObserver?.observe(track);
    window.addEventListener('resize', updateLoopDistance);
    window.addEventListener('load', updateLoopDistance);
  };

  page.querySelectorAll('.cp-gallery-row').forEach(initGalleryRow);

  const headerActions = document.getElementById('headerActions');
  const headerToggle = document.querySelector('.header-toggle');
  if (headerActions && headerToggle) {
    const closeMenu = () => {
      headerActions.classList.remove('header-actions--open');
      headerToggle.setAttribute('aria-expanded', 'false');
    };

    headerToggle.addEventListener('click', () => {
      const expanded = headerToggle.getAttribute('aria-expanded') === 'true';
      if (expanded) {
        closeMenu();
      } else {
        headerActions.classList.add('header-actions--open');
        headerToggle.setAttribute('aria-expanded', 'true');
      }
    });

    document.addEventListener('click', (event) => {
      if (!headerActions.contains(event.target)) {
        closeMenu();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) {
        closeMenu();
      }
    });
  }

  const faqButtons = page.querySelectorAll('.faq__card-button');
  faqButtons.forEach((button) => {
    const panel = document.getElementById(button.getAttribute('aria-controls') || '');
    button.addEventListener('click', () => {
      const expanded = button.getAttribute('aria-expanded') === 'true';
      faqButtons.forEach((btn) => {
        btn.setAttribute('aria-expanded', 'false');
        const targetPanel = document.getElementById(btn.getAttribute('aria-controls') || '');
        if (targetPanel) {
          targetPanel.hidden = true;
        }
      });
      if (!expanded) {
        button.setAttribute('aria-expanded', 'true');
        if (panel) {
          panel.hidden = false;
        }
      }
    });
  });
})();
