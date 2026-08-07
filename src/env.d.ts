/// <reference path="../.astro/types.d.ts" />

interface ImportMetaEnv {
  readonly PUBLIC_API_URL: string;
  readonly PUBLIC_SENDPULSE_ACCOUNT_ID: string;
  readonly PUBLIC_ADMIN_URL: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}