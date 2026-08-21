import {
  formatFechaLarga,
  formatHoraCorta,
  categoriaLabel,
  categoriaColor,
} from "./api";
import type { Actividad } from "./api";

const DIAS_ADELANTE = 15;
const CATEGORIAS_ICONO: Record<Actividad["categoria"], string> = {
  culto: "🕊️",
  estudio: "📖",
  evento: "🎉",
  ministerio: "🤝",
  social: "❤️",
  otro: "📌",
};

function escapeHtml(s: string): string {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function filtrarProximas(actividades: Actividad[], hoy: Date): Actividad[] {
  const out: Actividad[] = [];
  for (const a of actividades) {
    if (a.estado === "cancelada") continue;
    const fecha = new Date(a.fecha + "T00:00:00");
    const diffDias = Math.floor((fecha.getTime() - hoy.getTime()) / 86400000);
    if (diffDias >= 0 && diffDias < DIAS_ADELANTE) {
      out.push(a);
    }
  }
  return out;
}

function agruparPorFecha(items: Actividad[]): Record<string, Actividad[]> {
  const grouped: Record<string, Actividad[]> = {};
  for (const a of items) {
    grouped[a.fecha] ??= [];
    grouped[a.fecha].push(a);
  }
  for (const key of Object.keys(grouped)) {
    grouped[key].sort((a, b) => {
      const ha = a.hora_inicio ?? "";
      const hb = b.hora_inicio ?? "";
      return ha.localeCompare(hb);
    });
  }
  return grouped;
}

function renderVacio(): string {
  return `
    <div class="rounded-2xl border border-dashed border-white/10 p-8 text-center text-slate-400">
      No hay actividades programadas para los próximos ${DIAS_ADELANTE} días.
    </div>`;
}

function renderHora(a: Actividad): string {
  if (!a.hora_inicio) {
    return `<p class="text-xs text-slate-400 uppercase tracking-wider">Todo el día</p>`;
  }
  const [hh, mm] = formatHoraCorta(a.hora_inicio).split(":");
  const finTxt = a.hora_fin ? `–${formatHoraCorta(a.hora_fin)}` : "hs";
  return `
    <p class="text-2xl font-bold text-white leading-none">
      ${hh}<span class="text-[#D4AF37]">:</span>${mm}
    </p>
    <p class="text-[10px] text-slate-500 uppercase tracking-wider mt-1">${finTxt}</p>`;
}

function renderItem(a: Actividad): string {
  const catColor = categoriaColor(a.categoria);
  const desc = a.descripcion
    ? `<p class="text-sm text-slate-400 mt-1 line-clamp-2">${escapeHtml(a.descripcion)}</p>`
    : "";
  const lugar = a.lugar
    ? `
      <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        ${escapeHtml(a.lugar)}
      </p>`
    : "";
  const destacado = a.destacado
    ? `<span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full bg-[#D4AF37]/15 text-[#D4AF37]">Destacado</span>`
    : "";
  return `
    <li class="px-5 py-4 flex gap-4 hover:bg-white/[0.02] transition">
      <div class="shrink-0 w-16 text-center">${renderHora(a)}</div>
      <div class="flex-1 min-w-0">
        <div class="flex items-start gap-2 flex-wrap">
          <h4 class="font-semibold text-white">${escapeHtml(a.titulo)}</h4>
          <span
            class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full"
            style="background:${catColor}22;color:${catColor}"
          >
            ${categoriaLabel(a.categoria)}
          </span>
          ${destacado}
        </div>
        ${desc}
        ${lugar}
      </div>
    </li>`;
}

function renderGrupo(fecha: string, items: Actividad[]): string {
  return `
    <div class="rounded-2xl border border-white/5 bg-[#0c121e]/60 overflow-hidden">
      <div class="px-5 py-2 bg-white/5 text-xs uppercase tracking-wider text-[#D4AF37]">
        ${formatFechaLarga(fecha)}
      </div>
      <ul class="divide-y divide-white/5">
        ${items.map(renderItem).join("")}
      </ul>
    </div>`;
}

/**
 * Construye el HTML completo del resumen semanal.
 * Usado tanto en el SSR (con el seed del build) como en el cliente
 * (al refrescar contra la API), para mantener el render idéntico.
 */
export function buildResumenHTML(actividades: Actividad[], hoy?: Date): string {
  const ref = hoy ?? new Date(new Date().toISOString().slice(0, 10) + "T00:00:00");
  const proximas = filtrarProximas(actividades, ref);
  if (proximas.length === 0) return renderVacio();
  const grouped = agruparPorFecha(proximas);
  const orderedDays = Object.keys(grouped).sort();
  return `<div class="space-y-4">${orderedDays.map((d) => renderGrupo(d, grouped[d])).join("")}</div>`;
}

export function buildResumenHeader(actividades: Actividad[], hoy?: Date): string {
  const ref = hoy ?? new Date(new Date().toISOString().slice(0, 10) + "T00:00:00");
  const count = filtrarProximas(actividades, ref).length;
  return `
    <header class="flex items-baseline justify-between mb-2">
      <h3 class="text-2xl font-bold text-white">Próximos 15 días</h3>
      <span class="text-xs text-slate-400 uppercase tracking-wider">
        ${count} ${count === 1 ? "actividad" : "actividades"}
      </span>
    </header>`;
}
