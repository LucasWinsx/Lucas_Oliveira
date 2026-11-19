<?php
/* MODELO da configuração do assistente de IA (Google Gemini).
   Copie este arquivo para "ia.php" (na mesma pasta) e preencha a chave.
   O arquivo ia.php fica no .gitignore: NUNCA envie sua chave ao GitHub. */

// Chave criada no Google AI Studio (aistudio.google.com > "Get API key"). Plano gratuito, sem cartão.
const GEMINI_API_KEY = '';

// Modelo usado. Se aparecer erro 404, troque por um modelo "Flash" gratuito listado no AI Studio.
const GEMINI_MODEL = 'gemini-2.5-flash';

// Limite de perguntas por usuário a cada 10 minutos (protege a cota gratuita).
const ASSISTENTE_MAX_PERGUNTAS = 15;
