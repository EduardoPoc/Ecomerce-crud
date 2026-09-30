import { createIcons, icons } from 'lucide';

/**
 * Inicializa e substitui elementos com o atributo `data-lucide` pelos SVGs correspondentes.
 *
 * @param {Object} [options] - Opções de configuração
 * @param {Record<string, any>} [options.icons] - Conjunto de ícones específico (opcional; padrão: todos os ícones)
 * @param {Record<string, string|number>} [options.attrs] - Atributos extras aplicados aos SVGs gerados
 * @param {Element|Document} [options.root] - Elemento raiz onde buscar os ícones (padrão: document)
 * @param {boolean} [options.inTemplates] - Se deve processar ícones dentro de <template>
 */
export function initIcons(options = {}) {
  return createIcons({
    icons: options.icons || icons,
    attrs: options.attrs,
    root: options.root,
    inTemplates: options.inTemplates,
  });
}

export { createIcons, icons };
