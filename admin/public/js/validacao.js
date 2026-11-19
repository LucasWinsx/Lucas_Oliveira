/* Validação de formulários no navegador (a validação definitiva continua no PHP).
   Uso: <form data-validar> e, em cada campo, data-regra="email|nome|telefone|senha|senhaOpcional|preco|inteiro|cpf|data"
   e opcionalmente data-mascara="telefone|cpf". Campos com "required" também são verificados. */
(function () {
  'use strict';

  var css = '.campo-erro{display:block;margin-top:4px;font-size:12px;font-weight:600;color:#d93025;text-shadow:none}' +
            '.atv-cena .campo-erro{color:#ffb4ae}' +
            '[aria-invalid="true"]{border-color:#d93025 !important}';
  var estilo = document.createElement('style');
  estilo.textContent = css;
  document.head.appendChild(estilo);

  function digitos(v) { return String(v).replace(/\D+/g, ''); }

  function cpfValido(cpf) {
    cpf = digitos(cpf);
    if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
    for (var t = 9; t < 11; t++) {
      var soma = 0;
      for (var i = 0; i < t; i++) soma += parseInt(cpf.charAt(i), 10) * (t + 1 - i);
      if (((10 * soma) % 11) % 10 !== parseInt(cpf.charAt(t), 10)) return false;
    }
    return true;
  }

  // cada regra devolve true (ok) ou o texto do erro
  var regras = {
    nome: function (v) { return v.trim().length >= 2 || 'Informe o nome (mínimo 2 letras).'; },
    email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) || 'Informe um e-mail válido.'; },
    telefone: function (v) { var d = digitos(v); return (d.length === 10 || d.length === 11) || 'Informe o telefone com DDD (10 ou 11 dígitos).'; },
    senha: function (v, el) {
      var min = parseInt(el.getAttribute('minlength'), 10) || 6;
      return v.length >= min || 'A senha precisa ter ao menos ' + min + ' caracteres.';
    },
    senhaOpcional: function (v) { return v === '' || v.length >= 6 || 'A senha precisa ter ao menos 6 caracteres.'; },
    preco: function (v) {
      v = v.trim();
      return (/^\d+([.,]\d{1,2})?$/.test(v) || /^\d{1,3}(\.\d{3})+,\d{1,2}$/.test(v)) || 'Preço inválido. Exemplo: 229,90';
    },
    inteiro: function (v, el) {
      var min = el.getAttribute('min') !== null ? parseInt(el.getAttribute('min'), 10) : 0;
      return (/^\d+$/.test(v.trim()) && parseInt(v, 10) >= min) || 'Informe um número inteiro maior ou igual a ' + min + '.';
    },
    trailer: function (v) {
      v = v.trim();
      if (v === '') return true;
      var yt = /^https?:\/\/(www\.|m\.|music\.)?(youtube(-nocookie)?\.com|youtu\.be)\//i.test(v) &&
               /(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/))[A-Za-z0-9_-]{11}/i.test(v);
      var arq = /^(https?:\/\/\S+|[^\s\/\\]+)\.(mp4|webm|ogv)(\?\S*)?$/i.test(v);
      return (yt || arq) || 'Cole um link do YouTube ou de um vídeo .mp4/.webm.';
    },
    cpf: function (v) { return v.trim() === '' || cpfValido(v) || 'CPF inválido.'; },
    data: function (v) {
      if (v === '') return true;
      var d = new Date(v + 'T00:00:00');
      return (!isNaN(d.getTime()) && d <= new Date()) || 'Data inválida.';
    }
  };

  var mascaras = {
    telefone: function (v) {
      var d = digitos(v).slice(0, 11);
      if (d.length > 10) return d.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, '($1) $2-$3');
      if (d.length > 6)  return d.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
      if (d.length > 2)  return d.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
      return d ? '(' + d : '';
    },
    cpf: function (v) {
      var d = digitos(v).slice(0, 11);
      return d.replace(/^(\d{3})(\d)/, '$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1-$2');
    }
  };

  function limparErro(el) {
    el.removeAttribute('aria-invalid');
    var m = el.parentNode.querySelector('.campo-erro[data-de="' + (el.name || '') + '"]');
    if (m) m.remove();
  }

  function mostrarErro(el, texto) {
    limparErro(el);
    el.setAttribute('aria-invalid', 'true');
    var s = document.createElement('small');
    s.className = 'campo-erro';
    s.setAttribute('role', 'alert');
    s.setAttribute('data-de', el.name || '');
    s.textContent = texto;
    el.insertAdjacentElement('afterend', s);
  }

  // devolve true se o campo está válido
  function validarCampo(el) {
    if (el.disabled || el.type === 'hidden' || el.type === 'file' || el.type === 'submit') return true;
    var v = el.value;
    if (v.trim() === '' && !el.hasAttribute('required')) { limparErro(el); return true; }
    if (el.hasAttribute('required') && v.trim() === '') { mostrarErro(el, 'Preencha este campo.'); return false; }
    var nome = el.getAttribute('data-regra');
    if (nome && regras[nome]) {
      var r = regras[nome](v, el);
      if (r !== true) { mostrarErro(el, r); return false; }
    }
    limparErro(el);
    return true;
  }

  function iniciar(form) {
    form.noValidate = true;   // as mensagens abaixo substituem as do navegador
    var campos = form.querySelectorAll('input, select, textarea');
    Array.prototype.forEach.call(campos, function (el) {
      var m = el.getAttribute('data-mascara');
      if (m && mascaras[m]) {
        el.addEventListener('input', function () { el.value = mascaras[m](el.value); });
        if (el.value) el.value = mascaras[m](el.value);
      }
      el.addEventListener('blur', function () { validarCampo(el); });
      el.addEventListener('input', function () { if (el.getAttribute('aria-invalid') === 'true') validarCampo(el); });
    });
    form.addEventListener('submit', function (ev) {
      var primeiro = null;
      Array.prototype.forEach.call(campos, function (el) {
        if (!validarCampo(el) && !primeiro) primeiro = el;
      });
      if (primeiro) { ev.preventDefault(); primeiro.focus(); }
    });
  }

  function pronto() {
    Array.prototype.forEach.call(document.querySelectorAll('form[data-validar]'), iniciar);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', pronto); else pronto();
})();
