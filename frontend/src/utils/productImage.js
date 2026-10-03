const FALLBACK_IMAGE = '/images/image-unavailable.svg';

function getImageSource(source) {
  if (typeof source !== 'string' || source.trim() === '') return FALLBACK_IMAGE;
  if (source.includes('exemplo.com')) return FALLBACK_IMAGE;

  try {
    const url = new URL(source.trim(), window.location.origin);
    return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : FALLBACK_IMAGE;
  } catch {
    return FALLBACK_IMAGE;
  }
}

/** Cria a imagem do produto e usa o ícone de imagem indisponível se ela falhar. */
export function createProductImageElement(product, className = '') {
  const image = document.createElement('img');
  const imageName = typeof product?.nome === 'string' && product.nome.trim()
    ? product.nome.trim()
    : 'livro';
  const source = getImageSource(product?.imagem_url);
  let showingFallback = source === FALLBACK_IMAGE;

  image.className = className;
  image.alt = showingFallback
    ? `Imagem indisponível para ${imageName}`
    : `Capa de ${imageName}`;
  image.loading = 'lazy';
  image.decoding = 'async';
  image.style.backgroundColor = '#f5f3ee';
  image.style.backgroundImage = `url("${FALLBACK_IMAGE}")`;
  image.style.backgroundPosition = 'center';
  image.style.backgroundRepeat = 'no-repeat';
  image.style.backgroundSize = 'cover';
  image.addEventListener('error', () => {
    if (!showingFallback) {
      showingFallback = true;
      image.alt = `Imagem indisponível para ${imageName}`;
      image.src = FALLBACK_IMAGE;
      return;
    }

    const unavailable = document.createElement('span');
    unavailable.className = 'flex h-full w-full items-center justify-center px-2 text-center text-xs text-muted';
    unavailable.setAttribute('role', 'img');
    unavailable.setAttribute('aria-label', `Imagem indisponível para ${imageName}`);
    unavailable.textContent = 'Imagem indisponível';
    image.replaceWith(unavailable);
  });
  image.src = source;

  return image;
}
