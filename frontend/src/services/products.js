import api from './api.js';

const WINDOWS_1252_BYTES = new Map([
  [0x20ac, 0x80], [0x201a, 0x82], [0x0192, 0x83], [0x201e, 0x84],
  [0x2026, 0x85], [0x2020, 0x86], [0x2021, 0x87], [0x02c6, 0x88],
  [0x2030, 0x89], [0x0160, 0x8a], [0x2039, 0x8b], [0x0152, 0x8c],
  [0x017d, 0x8e], [0x2018, 0x91], [0x2019, 0x92], [0x201c, 0x93],
  [0x201d, 0x94], [0x2022, 0x95], [0x2013, 0x96], [0x2014, 0x97],
  [0x02dc, 0x98], [0x2122, 0x99], [0x0161, 0x9a], [0x203a, 0x9b],
  [0x0153, 0x9c], [0x017e, 0x9e], [0x0178, 0x9f],
]);

function byteValue(character) {
  const codePoint = character.codePointAt(0);
  return codePoint <= 0xff ? codePoint : WINDOWS_1252_BYTES.get(codePoint) ?? null;
}

export function repairMojibake(value) {
  if (typeof value !== 'string') return value;

  const characters = Array.from(value);
  let repaired = '';

  for (let index = 0; index < characters.length;) {
    const firstByte = byteValue(characters[index]);
    const sequenceLength = firstByte >= 0xc2 && firstByte <= 0xdf ? 2
      : firstByte >= 0xe0 && firstByte <= 0xef ? 3
        : firstByte >= 0xf0 && firstByte <= 0xf4 ? 4
          : 0;

    if (sequenceLength > 0 && index + sequenceLength <= characters.length) {
      const bytes = [firstByte];
      let isValidSequence = true;

      for (let offset = 1; offset < sequenceLength; offset += 1) {
        const nextByte = byteValue(characters[index + offset]);
        if (nextByte === null || nextByte < 0x80 || nextByte > 0xbf) {
          isValidSequence = false;
          break;
        }
        bytes.push(nextByte);
      }

      if (isValidSequence) {
        try {
          repaired += new TextDecoder('utf-8', { fatal: true }).decode(Uint8Array.from(bytes));
          index += sequenceLength;
          continue;
        } catch {
          // Mantém o texto original quando a sequência não for UTF-8 válida.
        }
      }
    }

    repaired += characters[index];
    index += 1;
  }

  return repaired;
}

/** Retorna a página de produtos no formato exposto pela API. */
export async function getProducts(params = {}) {
  const { data } = await api.get('/produtos', { params });
  if (!data || !Array.isArray(data.itens)) {
    throw new Error('A resposta de produtos da API está em um formato inesperado.');
  }

  return {
    ...data,
    itens: data.itens.map(normalizeProduct),
  };
}

export async function getProduct(id) {
  const { data } = await api.get(`/produtos/${id}`);
  return normalizeProduct(data);
}

function normalizeProduct(product) {
  return {
    ...product,
    nome: repairMojibake(product.nome),
    descricao: repairMojibake(product.descricao),
    categoria_nome: repairMojibake(product.categoria_nome),
  };
}

export function isProductActive(product) {
  return product.ativo === true || product.ativo === 1 || product.ativo === '1';
}

export function getProductStock(product) {
  const stock = Number(product.estoque);
  return Number.isFinite(stock) ? Math.max(0, Math.floor(stock)) : 0;
}

export function formatPrice(value) {
  const price = Number(value);
  if (!Number.isFinite(price)) return 'Preço indisponível';
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(price);
}
