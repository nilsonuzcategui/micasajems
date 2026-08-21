export interface Actividad {
  id: number;
  titulo: string;
  descripcion: string | null;
  lugar: string | null;
  fecha: string;
  hora_inicio: string | null;
  hora_fin: string | null;
  categoria: 'culto' | 'estudio' | 'evento' | 'ministerio' | 'social' | 'otro';
  destacado: boolean;
  estado: 'programada' | 'cancelada' | 'realizada';
  creado_por?: number;
  creado_por_nombre?: string;
  created_at?: string;
}

export type Categoria = Actividad['categoria'];
export type Estado = Actividad['estado'];

export const CATEGORIAS: Categoria[] = [
  'culto',
  'estudio',
  'evento',
  'ministerio',
  'social',
  'otro',
];

const API_BASE_DEFAULT = 'https://api.micasajems.com/api';

function getActividadesApiBase(): string {
  const env = (import.meta.env.PUBLIC_ACTIVIDADES_API ?? '').trim().replace(/\/+$/, '');
  return env === '' ? API_BASE_DEFAULT : env;
}

function getListUrl(): string {
  return `${getActividadesApiBase()}/get_actividades.php`;
}

function getDetailUrl(id: number | string): string {
  return `${getActividadesApiBase()}/get_actividad.php?id=${encodeURIComponent(String(id))}`;
}

function asCategoria(value: unknown): Categoria {
  return CATEGORIAS.includes(value as Categoria)
    ? (value as Categoria)
    : 'otro';
}

function asEstado(value: unknown): Estado {
  if (value === 'cancelada' || value === 'realizada' || value === 'programada') {
    return value;
  }
  return 'programada';
}

function normalizeActividad(raw: unknown): Actividad {
  const r = (raw ?? {}) as Record<string, unknown>;
  return {
    id: Number(r.id) || 0,
    titulo: String(r.titulo ?? ''),
    descripcion: r.descripcion == null ? null : String(r.descripcion),
    lugar: r.lugar == null ? null : String(r.lugar),
    fecha: String(r.fecha ?? ''),
    hora_inicio: r.hora_inicio == null || r.hora_inicio === '' ? null : String(r.hora_inicio),
    hora_fin: r.hora_fin == null || r.hora_fin === '' ? null : String(r.hora_fin),
    categoria: asCategoria(r.categoria),
    destacado: Boolean(Number(r.destacado) || r.destacado === true),
    estado: asEstado(r.estado),
    creado_por: r.creado_por == null ? undefined : Number(r.creado_por),
    creado_por_nombre: r.creado_por_nombre == null ? undefined : String(r.creado_por_nombre),
    created_at: r.created_at == null ? undefined : String(r.created_at),
  };
}

const TIMEOUT_MS = 9000;

async function fetchConTimeout(url: string): Promise<Response> {
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);
  try {
    return await fetch(url, {
      method: 'GET',
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    });
  } finally {
    clearTimeout(timeoutId);
  }
}

interface FetchParams {
  desde?: string;
  hasta?: string;
  categoria?: string;
  estado?: string;
  incluirCanceladas?: boolean;
}

function buildQuery(params: FetchParams): string {
  const search = new URLSearchParams();
  if (params.desde) search.set('desde', params.desde);
  if (params.hasta) search.set('hasta', params.hasta);
  if (params.categoria) search.set('categoria', params.categoria);
  if (params.estado) search.set('estado', params.estado);
  if (params.incluirCanceladas) search.set('incluir_canceladas', '1');
  const qs = search.toString();
  return qs ? `?${qs}` : '';
}

async function fetchActividadesRaw(params: FetchParams = {}): Promise<Actividad[]> {
  const url = `${getListUrl()}${buildQuery(params)}`;
  const res = await fetchConTimeout(url);
  if (!res.ok) {
    throw new Error(`HTTP ${res.status} al pedir actividades`);
  }
  const data = await res.json();
  if (!Array.isArray(data)) {
    throw new Error('La respuesta de actividades no es un array válido');
  }
  return data.map(normalizeActividad);
}

export async function fetchActividades(params: FetchParams = {}): Promise<Actividad[]> {
  return fetchActividadesRaw(params);
}

export async function fetchActividadDetalle(id: number): Promise<Actividad> {
  const res = await fetchConTimeout(getDetailUrl(id));
  if (!res.ok) {
    throw new Error(`HTTP ${res.status} al pedir la actividad ${id}`);
  }
  const data = await res.json();
  if (!data || typeof data !== 'object' || Array.isArray(data)) {
    throw new Error('Respuesta inválida del detalle de actividad');
  }
  return normalizeActividad(data);
}

interface CacheEntry {
  ts: number;
  data: Actividad[];
}

const rangeCache = new Map<string, CacheEntry>();
const CACHE_TTL_MS = 5 * 60 * 1000;

export async function fetchActividadesPorRango(
  desde: string,
  hasta: string,
  opts: { incluirCanceladas?: boolean; bypassCache?: boolean } = {},
): Promise<Actividad[]> {
  const key = `${desde}|${hasta}|${opts.incluirCanceladas ? '1' : '0'}`;
  if (!opts.bypassCache) {
    const cached = rangeCache.get(key);
    if (cached && Date.now() - cached.ts < CACHE_TTL_MS) {
      return cached.data;
    }
  }
  const data = await fetchActividadesRaw({
    desde,
    hasta,
    incluirCanceladas: opts.incluirCanceladas,
  });
  rangeCache.set(key, { ts: Date.now(), data });
  return data;
}

export function invalidateActividadesCache(): void {
  rangeCache.clear();
}

function getJSON<T>(path: string): Promise<T> {
  const envBase = (import.meta.env.PUBLIC_API_URL ?? '').trim().replace(/\/+$/, '');
  let url: string;
  if (envBase === '') {
    url = path.startsWith('/') ? path : '/' + path;
  } else {
    const base = envBase.endsWith('/api') ? envBase : envBase + '/api';
    const cleanPath = path.startsWith('/') ? path : '/' + path;
    url = base + cleanPath;
  }
  return fetch(url, { headers: { Accept: 'application/json' } }).then(async (res) => {
    if (!res.ok) throw new Error(`HTTP ${res.status} al pedir ${path}`);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Error desconocido');
    return json as T;
  });
}

export async function logSuscripcion(): Promise<void> {
  await getJSON<{ ok: true; data: { id: number } }>('/suscripciones');
}

export function formatHoraCorta(hora: string | null | undefined): string {
  if (!hora) return '';
  return hora.slice(0, 5);
}

export function formatFechaCorta(fecha: string): string {
  const d = new Date(fecha + 'T00:00:00');
  return d.toLocaleDateString('es-VE', { weekday: 'short', day: '2-digit', month: 'short' });
}

export function formatFechaLarga(fecha: string): string {
  const d = new Date(fecha + 'T00:00:00');
  return d.toLocaleDateString('es-VE', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
}

export function categoriaLabel(c: Categoria): string {
  const labels: Record<Categoria, string> = {
    culto: 'Culto',
    estudio: 'Estudio Bíblico',
    evento: 'Evento',
    ministerio: 'Ministerio',
    social: 'Acción Social',
    otro: 'Otro',
  };
  return labels[c];
}

export function categoriaColor(c: Categoria): string {
  const colors: Record<Categoria, string> = {
    culto: '#D4AF37',
    estudio: '#60a5fa',
    evento: '#f472b6',
    ministerio: '#a78bfa',
    social: '#34d399',
    otro: '#94a3b8',
  };
  return colors[c];
}
