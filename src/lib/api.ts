export interface Actividad {
  id: number;
  titulo: string;
  descripcion: string | null;
  lugar: string;
  fecha: string;
  hora_inicio: string;
  hora_fin: string | null;
  categoria: 'culto' | 'estudio' | 'evento' | 'ministerio' | 'social' | 'otro';
  destacado: boolean;
  estado: 'programada' | 'cancelada' | 'realizada';
  created_at?: string;
}

export interface ActividadListResponse {
  ok: true;
  data: Actividad[];
}

export interface SemanaResponse {
  ok: true;
  data: {
    semana: string;
    items: Actividad[];
  };
}

const DEFAULT_BASE = 'http://localhost/micasajems/backend/public';

function getBase(): string {
  if (typeof import.meta !== 'undefined' && import.meta.env) {
    const v = import.meta.env.PUBLIC_API_URL;
    if (typeof v === 'string' && v.length > 0) {
      return v.replace(/\/$/, '');
    }
  }
  return DEFAULT_BASE;
}

async function getJSON<T>(path: string): Promise<T> {
  const res = await fetch(`${getBase()}${path}`, {
    headers: { Accept: 'application/json' },
  });
  if (!res.ok) {
    throw new Error(`HTTP ${res.status} al pedir ${path}`);
  }
  const json = await res.json();
  if (!json.ok) {
    throw new Error(json.error || 'Error desconocido');
  }
  return json as T;
}

export async function fetchActividades(params: { desde?: string; hasta?: string } = {}): Promise<Actividad[]> {
  const search = new URLSearchParams();
  if (params.desde) search.set('desde', params.desde);
  if (params.hasta) search.set('hasta', params.hasta);
  const qs = search.toString();
  const path = `/api/actividades${qs ? `?${qs}` : ''}`;
  const res = await getJSON<ActividadListResponse>(path);
  return res.data;
}

export async function fetchSemana(anchorDate?: string): Promise<Actividad[]> {
  const q = anchorDate ? `?semana=${anchorDate}` : '';
  const res = await getJSON<SemanaResponse>(`/api/actividades${q}`);
  return res.data.items;
}

export async function fetchActividad(id: number): Promise<Actividad> {
  const res = await getJSON<{ ok: true; data: Actividad }>(`/api/actividades/${id}`);
  return res.data;
}

export function formatFechaCorta(fecha: string): string {
  const d = new Date(fecha + 'T00:00:00');
  return d.toLocaleDateString('es-VE', { weekday: 'short', day: '2-digit', month: 'short' });
}

export function formatFechaLarga(fecha: string): string {
  const d = new Date(fecha + 'T00:00:00');
  return d.toLocaleDateString('es-VE', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
}

export function categoriaLabel(c: Actividad['categoria']): string {
  const labels: Record<Actividad['categoria'], string> = {
    culto: 'Culto',
    estudio: 'Estudio Bíblico',
    evento: 'Evento',
    ministerio: 'Ministerio',
    social: 'Acción Social',
    otro: 'Otro',
  };
  return labels[c];
}

export function categoriaColor(c: Actividad['categoria']): string {
  const colors: Record<Actividad['categoria'], string> = {
    culto: '#D4AF37',
    estudio: '#60a5fa',
    evento: '#f472b6',
    ministerio: '#a78bfa',
    social: '#34d399',
    otro: '#94a3b8',
  };
  return colors[c];
}