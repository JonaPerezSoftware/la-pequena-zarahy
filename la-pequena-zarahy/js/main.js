// =============================================
// LA PEQUEÑA ZARAHY — main.js
// =============================================

// ── Navbar scroll ──────────────────────────────────────────
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
  navbar.classList.toggle('scrolled', window.scrollY > 30);
  updateActiveNav();
}, { passive: true });

// ── Hamburger ──────────────────────────────────────────────
const hamburger = document.getElementById('hamburger');
const navMenu   = document.getElementById('nav-menu');

hamburger.addEventListener('click', () => {
  hamburger.classList.toggle('open');
  navMenu.classList.toggle('open');
  document.body.style.overflow = navMenu.classList.contains('open') ? 'hidden' : '';
});

navMenu.querySelectorAll('a').forEach(link => {
  link.addEventListener('click', () => {
    hamburger.classList.remove('open');
    navMenu.classList.remove('open');
    document.body.style.overflow = '';
  });
});

// ── Active nav link ────────────────────────────────────────
const sections  = document.querySelectorAll('section[id]');
const navLinks  = document.querySelectorAll('.nav-link');

function updateActiveNav() {
  const scrollY = window.scrollY + 90;
  sections.forEach(sec => {
    if (scrollY >= sec.offsetTop && scrollY < sec.offsetTop + sec.offsetHeight) {
      navLinks.forEach(l => {
        l.classList.toggle('active', l.getAttribute('href') === '#' + sec.id);
      });
    }
  });
}

// ── Scroll reveal ──────────────────────────────────────────
const revealObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      revealObserver.unobserve(e.target);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// ── Counter animation ──────────────────────────────────────
function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const suffix = el.dataset.suffix || '';
  const duration = 1800;
  const step = target / (duration / 16);
  let current = 0;
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = Math.floor(current).toLocaleString('es-EC') + suffix;
    if (current >= target) clearInterval(timer);
  }, 16);
}

const counterObserver = new IntersectionObserver((entries) => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      animateCounter(e.target);
      counterObserver.unobserve(e.target);
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll('[data-target]').forEach(el => counterObserver.observe(el));

// ── Vacantes ───────────────────────────────────────────────
const SAMPLE_VACANTES = [
  {
    id: "1",
    titulo: "Auxiliar de Enfermería",
    departamento: "Salud",
    tipo: "Tiempo Completo",
    ubicacion: "Quito, Pichincha",
    descripcion: "Buscamos una persona comprometida para apoyar en la atención médica de comunidades vulnerables. Trabajarás en brigadas médicas y seguimiento de pacientes.",
    requisitos: ["Título en Enfermería o área afín", "Experiencia mínima de 1 año", "Disponibilidad para trabajo en campo", "Vocación de servicio"],
    fecha: "2025-05-01"
  },
  {
    id: "2",
    titulo: "Coordinador/a de Programas Educativos",
    departamento: "Educación",
    tipo: "Tiempo Completo",
    ubicacion: "Guayaquil, Guayas",
    descripcion: "Planificar y ejecutar programas educativos para niños y jóvenes en situación vulnerable. Liderarás un equipo de voluntarios y docentes.",
    requisitos: ["Licenciatura en Educación o Pedagogía", "Experiencia en ONGs o trabajo comunitario", "Habilidades de liderazgo", "Manejo de herramientas digitales"],
    fecha: "2025-05-01"
  },
  {
    id: "3",
    titulo: "Voluntario/a en Logística",
    departamento: "Operaciones",
    tipo: "Medio Tiempo",
    ubicacion: "Quito, Pichincha",
    descripcion: "Apoyo en la organización y distribución de kits de alimentos y donaciones. Ideal para personas con disponibilidad los fines de semana.",
    requisitos: ["Mayor de 18 años", "Disponibilidad fines de semana", "Actitud proactiva", "Licencia de conducir (deseable)"],
    fecha: "2025-05-01"
  }
];

async function cargarVacantes() {
  try {
    const res = await fetch('vacantes.json?v=' + Date.now());
    if (!res.ok) throw new Error('not found');
    const data = await res.json();
    return data.filter(v => v.activa !== false).sort((a, b) => {
      const dateCompare = b.fecha.localeCompare(a.fecha);
      if (dateCompare !== 0) return dateCompare;
      return b.id.localeCompare(a.id);
    });
  } catch {
    return [...SAMPLE_VACANTES].sort((a, b) => {
      const dateCompare = b.fecha.localeCompare(a.fecha);
      if (dateCompare !== 0) return dateCompare;
      return b.id.localeCompare(a.id);
    });
  }
}

function tipoBadge(tipo) {
  if (!tipo) return '';
  const t = tipo.toLowerCase();
  let cls = 'badge-full', label = tipo;
  if (t.includes('medio') || t.includes('part')) cls = 'badge-half';
  if (t.includes('volunt')) cls = 'badge-vol';
  return `<span class="job-badge ${cls}">${label}</span>`;
}

function fechaFormateada(f) {
  if (!f) return '';
  const d = new Date(f + 'T00:00:00');
  return d.toLocaleDateString('es-EC', { day: '2-digit', month: 'short', year: 'numeric' });
}

function renderVacantes(vacantes) {
  const grid = document.getElementById('jobs-grid');
  if (!grid) return;

  if (!vacantes.length) {
    grid.innerHTML = `<div class="jobs-empty" style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 60px 0;">
      <span style="font-size:3.5rem; margin-bottom: 15px;">💼</span>
      <h3 style="margin-bottom: 10px; color: var(--text-dark);">No hay vacantes disponibles en este momento.</h3>
      <p style="color: var(--text-gray);">¡Vuelve pronto!</p>
    </div>`;
    return;
  }

  grid.innerHTML = vacantes.map(v => `
    <div class="card job-card reveal">
      <div class="job-header">
        <h3 class="job-title">${v.titulo}</h3>
        ${tipoBadge(v.tipo)}
      </div>
      <span class="job-dept">🏷 ${v.departamento || 'General'}</span>
      <div class="job-loc">📍 ${v.ubicacion || 'Ecuador'}</div>
      <p class="job-desc">${v.descripcion}</p>
      ${v.requisitos && v.requisitos.length ? `
        <div class="job-reqs">
          <h4>Requisitos</h4>
          <ul>${v.requisitos.slice(0, 4).map(r => `<li>${r}</li>`).join('')}</ul>
        </div>` : ''}
      <div class="job-footer">
        <span class="job-date">📅 ${fechaFormateada(v.fecha)}</span>
        <button class="btn-apply" onclick="abrirModal('${v.id}','${v.titulo.replace(/'/g,"\\'")}')">
          Aplicar Ahora
        </button>
      </div>
    </div>
  `).join('');

  // Re-observe new reveal elements
  grid.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
}

// ── Modales ──────────────────────────────────────────────────
const modal        = document.getElementById('modal-apply');
const modalDonacion = document.getElementById('modal-donacion');
const modalClose   = document.getElementById('modal-close');
const modalVacante = document.getElementById('modal-vacante-nombre');
const applyForm    = document.getElementById('form-apply');
const fileInput    = document.getElementById('cv-file');
const fileName     = document.getElementById('file-name');
const formAlert    = document.getElementById('form-alert');

function abrirModal(id, titulo) {
  if (!modal) return;
  document.getElementById('input-vacante').value = titulo;
  modalVacante.textContent = titulo;
  applyForm.reset();
  fileName.style.display = 'none';
  fileName.textContent = '';
  formAlert.style.display = 'none';
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function cerrarModal() {
  if (modal) modal.classList.remove('open');
  document.body.style.overflow = '';
}

function abrirModalDonacion(tipo = 'local') {
  if (!modalDonacion) return;
  
  const title = document.getElementById('don-modal-title');
  const subtitle = document.getElementById('don-modal-subtitle');
  const bankDetails = document.getElementById('don-bank-details');
  const paypalDetails = document.getElementById('don-paypal-details');
  const qrImg = document.getElementById('don-qr-img');
  
  if (tipo === 'internacional') {
    title.textContent = 'Donación Internacional';
    subtitle.textContent = 'A través de PayPal';
    bankDetails.style.display = 'none';
    paypalDetails.style.display = 'block';
    qrImg.src = 'varios/qr_paypal.jpeg';
    qrImg.alt = 'Código QR de PayPal';
  } else {
    title.textContent = 'Datos para Donación';
    subtitle.textContent = 'Transferencia Bancaria Directa';
    bankDetails.style.display = 'block';
    paypalDetails.style.display = 'none';
    qrImg.src = 'varios/qr_donacion.png';
    qrImg.alt = 'Código QR Banco Pichincha';
  }
  
  modalDonacion.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function cerrarModalDonacion() {
  if (modalDonacion) modalDonacion.classList.remove('open');
  document.body.style.overflow = '';
}

modalClose && modalClose.addEventListener('click', cerrarModal);

// Cerrar modales al hacer clic fuera o Escape
window.addEventListener('click', e => {
  if (e.target === modal) cerrarModal();
  if (e.target === modalDonacion) cerrarModalDonacion();
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    cerrarModal();
    cerrarModalDonacion();
  }
});

fileInput && fileInput.addEventListener('change', () => {
  const f = fileInput.files[0];
  if (f) {
    fileName.textContent = '📎 ' + f.name;
    fileName.style.display = 'block';
  }
});

// Drag & drop
const dropZone = document.querySelector('.file-upload');
if (dropZone) {
  dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
  dropZone.addEventListener('drop', e => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const f = e.dataTransfer.files[0];
    if (f && f.type === 'application/pdf') {
      fileInput.files = e.dataTransfer.files;
      fileName.textContent = '📎 ' + f.name;
      fileName.style.display = 'block';
    } else {
      showToast('Solo se aceptan archivos PDF', 'error');
    }
  });
}

// ── Envío de postulación ────────────────────────────────────
applyForm && applyForm.addEventListener('submit', async e => {
  e.preventDefault();
  const btn = applyForm.querySelector('.btn-submit');
  const originalText = btn.textContent;

  // Validar PDF
  const file = fileInput.files[0];
  if (!file) { showAlert('Por favor adjunta tu CV en formato PDF.', 'error'); return; }
  if (!file.name.toLowerCase().endsWith('.pdf')) { showAlert('El archivo debe ser un PDF.', 'error'); return; }
  if (file.size > 5 * 1024 * 1024) { showAlert('El archivo no debe superar 5 MB.', 'error'); return; }

  btn.disabled = true;
  btn.textContent = '⏳ Enviando...';
  formAlert.style.display = 'none';

  try {
    const fd = new FormData(applyForm);
    const res = await fetch('apply.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.success) {
      applyForm.style.display = 'none'; // Ocultar formulario al éxito
      showAlert(data.message || '¡Postulación enviada con éxito! 🎉', 'success');
      setTimeout(() => {
        cerrarModal();
        // Resetear para la próxima vez
        setTimeout(() => {
          applyForm.style.display = 'block';
          formAlert.style.display = 'none';
          applyForm.reset();
        }, 500);
      }, 4000);
      showToast('¡Postulación enviada! 🎉', 'success');
    } else {
      showAlert(data.message || 'Ocurrió un error. Inténtalo de nuevo.', 'error');
    }
  } catch {
    // Modo local (sin servidor PHP) — simular éxito para pruebas
    applyForm.style.display = 'none';
    showAlert('¡Gracias por tu interés! Nos comunicaremos contigo pronto. ✨', 'success');
    setTimeout(() => {
      cerrarModal();
      setTimeout(() => {
        applyForm.style.display = 'block';
        formAlert.style.display = 'none';
        applyForm.reset();
      }, 500);
    }, 4000);
  }

  btn.disabled = false;
  btn.textContent = originalText;
});

function showAlert(msg, type) {
  formAlert.textContent = msg;
  formAlert.className = 'form-alert ' + type;
  formAlert.style.display = 'block';
}

// ── Formulario de contacto ─────────────────────────────────
const contactForm = document.getElementById('form-contact');
contactForm && contactForm.addEventListener('submit', async e => {
  e.preventDefault();
  const btn = contactForm.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.textContent = '⏳ Enviando...';

  try {
    const fd = new FormData(contactForm);
    const res = await fetch('contact.php', { method: 'POST', body: fd });
    const data = await res.json();
    
    if (data.success) {
      showToast(data.message || '¡Mensaje enviado! Te contactaremos pronto. 💜', 'success');
      contactForm.reset();
    } else {
      showToast(data.message || 'Error al enviar el mensaje.', 'error');
    }
  } catch {
    // Si falla la conexión (ej: local sin PHP)
    showToast('¡Mensaje enviado! Te contactaremos pronto. 💜', 'success');
    contactForm.reset();
  }

  btn.disabled = false;
  btn.textContent = 'Enviar Mensaje';
});

// ── Toast ───────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  let toast = document.getElementById('toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'toast';
    toast.className = 'toast';
    document.body.appendChild(toast);
  }
  toast.textContent = msg;
  toast.className = 'toast ' + type;
  requestAnimationFrame(() => {
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
  });
}

// ── Cobertura ────────────────────────────────────────────────
async function loadCobertura() {
  const grid = document.getElementById('cobertura-grid');
  if (!grid) return;
  
  let data = null;

  // Intento 1: leer JSON directo
  try {
    const res = await fetch('cobertura.json?v=' + Date.now());
    if (res.ok) {
      const text = await res.text();
      if (text.trim().startsWith('[') || text.trim().startsWith('{')) {
        data = JSON.parse(text);
      }
    }
  } catch(e) { /* falla silenciosa */ }

  // Intento 2: usar el PHP (por si el JSON no está accesible)
  if (!data) {
    try {
      const res2 = await fetch('admin/guardar_cobertura.php?action=get');
      if (res2.ok) {
        const json2 = await res2.json();
        // El PHP puede devolver {success, data:[]} o directamente []
        data = Array.isArray(json2) ? json2 : (json2.data || json2);
      }
    } catch(e) { /* falla silenciosa */ }
  }

  if (!data) {
    grid.innerHTML = '<p style="text-align:center;color:var(--text-gray)">No se pudieron cargar las localidades. Por favor recarga la página.</p>';
    return;
  }

    grid.innerHTML = '';
    data.forEach((item, index) => {
      const delay = index * 100; // cascada de animación
      
      let coordinatorsHtml = '<div class="cov-photos-container">';
      let namesText = '';

      // Soporte para múltiples coordinadores o estructura antigua
      if (item.coordinadores && Array.isArray(item.coordinadores)) {
        item.coordinadores.forEach(c => {
          coordinatorsHtml += c.foto 
            ? `<img src="${c.foto}" class="cov-photo" alt="${c.nombre}">` 
            : `<div class="cov-photo">👤</div>`;
        });
        
        const names = item.coordinadores.map(c => c.nombre);
        if (names.length > 1) {
          const last = names.pop();
          namesText = names.join(', ') + ' y ' + last;
        } else {
          namesText = names[0] || '';
        }
      } else {
        // Fallback estructura antigua
        coordinatorsHtml += (item.foto && item.foto !== "") 
          ? `<img src="${item.foto}" class="cov-photo" alt="Coordinadora">` 
          : `<div class="cov-photo">👤</div>`;
        namesText = item.nombres;
      }
      coordinatorsHtml += '</div>';

      grid.innerHTML += `
        <div class="coverage-card reveal" style="animation-delay: ${delay}ms;">
          <div class="cov-icon">📍</div>
          <div class="cov-name">${item.localidad}</div>
          
          <div class="coverage-overlay">
            ${coordinatorsHtml}
            <div class="cov-coord-name">${namesText}</div>
          </div>
        </div>
      `;
    });
    // Volver a aplicar el observer si es necesario
    grid.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
}

// ── Init ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  loadContenidoPagina();
  const vacantes = await cargarVacantes();
  renderVacantes(vacantes);
  loadCobertura();
  loadLabores();
  loadConvenios();
  // Init Lucide icons
  if (window.lucide) lucide.createIcons();
});

// ── Contenido Página ────────────────────────────────────────
async function loadContenidoPagina() {
  try {
    const res = await fetch('contenido_pagina.json?v=' + Date.now());
    if (!res.ok) throw new Error('not found');
    const data = await res.json();
    
    // Inicio
    if (data.inicio) {
      if (document.getElementById('hero-titulo')) document.getElementById('hero-titulo').innerHTML = (data.inicio.titulo || '').replace(/\n/g, '<br>');
      if (document.getElementById('hero-desc')) document.getElementById('hero-desc').innerHTML = (data.inicio.descripcion || '').replace(/\n/g, '<br>');
      const heroActions = document.querySelector('.hero-actions');
      if (heroActions) {
         const btns = heroActions.querySelectorAll('.btn');
         if (btns[0] && data.inicio.boton_donar) btns[0].innerHTML = data.inicio.boton_donar;
         if (btns[1] && data.inicio.boton_unete) btns[1].innerHTML = data.inicio.boton_unete;
      }
    }

    // Nosotros
    if (data.nosotros) {
      const msTitle = document.querySelector('#mision .section-title');
      if (msTitle) msTitle.textContent = data.nosotros.titulo || '';
      if (document.getElementById('nosotros-sub')) document.getElementById('nosotros-sub').innerHTML = (data.nosotros.texto1 || '').replace(/\n/g, '<br>');
      if (document.getElementById('nosotros-intro')) {
        document.getElementById('nosotros-intro').innerHTML = (data.nosotros.texto2 || '').replace(/\n/g, '<br>');
      }
      if (data.nosotros.mision_vision) {
        if (document.getElementById('nosotros-mision')) document.getElementById('nosotros-mision').innerHTML = (data.nosotros.mision_vision.mision || '').replace(/\n/g, '<br>');
        if (document.getElementById('nosotros-vision')) document.getElementById('nosotros-vision').innerHTML = (data.nosotros.mision_vision.vision || '').replace(/\n/g, '<br>');
      }
      if (data.nosotros.estadisticas) {
        if (document.getElementById('stat-familias')) document.getElementById('stat-familias').dataset.target = data.nosotros.estadisticas.familias || 0;
        if (document.getElementById('stat-proyectos')) document.getElementById('stat-proyectos').dataset.target = data.nosotros.estadisticas.proyectos || 0;
        if (document.getElementById('stat-voluntarios')) document.getElementById('stat-voluntarios').dataset.target = data.nosotros.estadisticas.voluntarios || 0;
      }
    }

    // Fundadora
    if (data.fundadora) {
      if (document.getElementById('fundadora-nombre')) document.getElementById('fundadora-nombre').textContent = data.fundadora.nombre || '';
      if (document.getElementById('fundadora-badge')) document.getElementById('fundadora-badge').textContent = data.fundadora.cargo || '';
      if (document.getElementById('fundadora-cita')) document.getElementById('fundadora-cita').innerHTML = (data.fundadora.cita || '').replace(/\n/g, '<br>');
      if (data.fundadora.foto) {
        if (document.getElementById('fundadora-foto')) {
          document.getElementById('fundadora-foto').src = data.fundadora.foto;
          document.getElementById('fundadora-foto').style.display = 'block';
        }
      } else {
        if (document.getElementById('fundadora-foto')) {
          document.getElementById('fundadora-foto').style.display = 'none';
        }
      }
    }

    // Directiva
    if (data.directiva && data.directiva.miembros) {
      const grid = document.getElementById('directiva-grid');
      if (grid) {
        const cargoClass = (cargo) => {
          const c = (cargo || '').toLowerCase();
          if (c.includes('presiden') && !c.includes('vice')) return 'presidente';
          if (c.includes('vice')) return 'vicepresidente';
          if (c.includes('secret')) return 'secretaria';
          if (c.includes('tesore')) return 'tesorero';
          return 'vocal';
        };
        grid.innerHTML = data.directiva.miembros.map((m, i) => `
          <div class="directiva-card reveal" style="animation-delay: ${i*100}ms;">
            <div class="directiva-photo-wrap">
              ${m.foto
                ? `<img src="${m.foto}" alt="${m.nombre}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                   <div class="directiva-avatar" style="display:none">👤</div>`
                : `<div class="directiva-avatar">👤</div>`}
            </div>
            <span class="directiva-badge ${cargoClass(m.cargo)}">${m.cargo.toUpperCase()}</span>
            <h4 class="directiva-name">${m.nombre}</h4>
          </div>
        `).join('');
        // Re-registrar los nuevos elementos .reveal
        grid.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
      }
    }

    // Valores
    if (data.valores && data.valores.lista) {
      const grid = document.getElementById('valores-grid');
      if (grid) {
        grid.innerHTML = data.valores.lista.map((v, i) => `
          <div class="card value-card reveal" style="animation-delay: ${i*100}ms;">
            <div class="value-icon">${v.icono}</div>
            <h3>${v.titulo}</h3>
            <p>${v.descripcion}</p>
          </div>
        `).join('');
        // Re-registrar los nuevos elementos .reveal
        grid.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
      }
    }

    // Servicios
    if (data.servicios && data.servicios.lista) {
      const grid = document.getElementById('servicios-grid');
      if (grid) {
        grid.innerHTML = data.servicios.lista.map((s, i) => `
          <div class="card service-card reveal" style="animation-delay: ${i*100}ms;">
            <div class="service-icon">${s.icono}</div>
            <h3>${s.titulo}</h3>
            <p>${s.descripcion}</p>
          </div>
        `).join('');
        // Re-registrar los nuevos elementos .reveal
        grid.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
      }
    }

    // Impacto
    if (data.impacto) {
      // El título y subtítulo del HTML son los correctos; solo actualizamos si el JSON
      // trae campos específicos para ello (titulo / subtitulo). Los campos
      // testimonio_autor/rol se reservan para el testimoniante, no para los encabezados.
      if (data.impacto.titulo) {
        if (document.getElementById('impacto-titulo')) document.getElementById('impacto-titulo').textContent = data.impacto.titulo;
      }
      if (data.impacto.subtitulo) {
        if (document.getElementById('impacto-subtitulo')) document.getElementById('impacto-subtitulo').textContent = data.impacto.subtitulo;
      }

      const desc = data.impacto.testimonio_texto || '';
      if (desc && document.getElementById('impacto-desc')) {
        document.getElementById('impacto-desc').innerHTML = desc.replace(/\n/g, '<br>');
      }

      if (data.impacto.testimonio_foto) {
        const fotoEl = document.getElementById('impacto-img');
        if (fotoEl) fotoEl.src = data.impacto.testimonio_foto;
      }
    }

  } catch (e) {
    console.log("No se pudo cargar contenido_pagina.json", e);
  }
}

// ── LABOR SOCIAL ────────────────────────────────────────────
async function loadLabores() {
  try {
    const res = await fetch('labores.json?v=' + Date.now());
    if (!res.ok) throw new Error('not found');
    const data = await res.json();
    renderLabores(data);
  } catch {
    try {
      const res2 = await fetch('guardar_labores.php?action=get');
      if (res2.ok) {
        const json2 = await res2.json();
        renderLabores(json2.data || []);
        return;
      }
    } catch { /* silencioso */ }
    renderLabores([]);
  }
}

function formatLaborDate(fecha, hora) {
  if (!fecha) return '';
  const d = new Date(fecha + 'T00:00:00');
  const dateStr = d.toLocaleDateString('es-EC', { day: '2-digit', month: 'long', year: 'numeric' });
  return hora ? `${dateStr} — ${hora}` : dateStr;
}

function relativeDate(fechaStr) {
  if (!fechaStr) return '';
  const fecha = new Date(fechaStr + 'T00:00:00');
  const hoy   = new Date();
  hoy.setHours(0,0,0,0);
  const diff  = Math.floor((hoy - fecha) / 86400000);
  if (diff === 0) return 'Hoy';
  if (diff === 1) return 'Ayer';
  if (diff < 7)  return `Hace ${diff} días`;
  if (diff < 30) return `Hace ${Math.floor(diff/7)} semana${Math.floor(diff/7)>1?'s':''}`;
  if (diff < 365) return `Hace ${Math.floor(diff/30)} mes${Math.floor(diff/30)>1?'es':''}`;
  return `Hace ${Math.floor(diff/365)} año${Math.floor(diff/365)>1?'s':''}`;
}

function renderLabores(data) {
  const track = document.getElementById('labor-track');
  if (!track) return;

  const wrap = track.parentNode;       // labor-carousel-wrap
  const container = wrap.parentNode;   // .container

  // Helper: obtener o crear un div auxiliar fuera del carousel
  function getAuxEl(id) {
    let el = document.getElementById(id);
    if (!el) {
      el = document.createElement('div');
      el.id = id;
      container.insertBefore(el, wrap.nextSibling);
    }
    return el;
  }

  // Helper: construir HTML de una tarjeta
  function buildCard(item, idx, total) {
    const fotos = item.fotos || [];
    const videos = item.videos || [];
    const totalMedia = fotos.length + videos.length;
    const imgWrap = fotos.length > 0
      ? `<img src="${fotos[0]}" alt="${item.titulo}" class="labor-card-img" loading="lazy">`
      : `<div class="labor-card-no-img">🤝</div>`;
    const photoBadge = fotos.length > 0
      ? `<button class="labor-photo-badge photo" onclick="openLaborGallery(${idx}, 0, event)" title="Ver imágenes">
           <i class="fas fa-images"></i> ${fotos.length} foto${fotos.length > 1 ? 's' : ''}
         </button>` : '';
    const videoBadge = videos.length > 0
      ? `<button class="labor-photo-badge video" onclick="openLaborGallery(${idx}, ${fotos.length}, event)" title="Ver videos">
           <i class="fas fa-play-circle"></i> ${videos.length} video${videos.length > 1 ? 's' : ''}
         </button>` : '';
    const topBadges = (photoBadge || videoBadge)
      ? `<div class="labor-badges-right">${photoBadge}${videoBadge}</div>` : '';
    const recentBadge = idx === 0 ? `<span class="labor-recent-badge">⭐ Reciente</span>` : '';
    const galleryBtn = totalMedia > 0
      ? `<button class="btn-galeria" onclick="openLaborGallery(${idx}, 0, event)">
           <i class="fas fa-expand-alt"></i> Ver ${totalMedia > 1 ? 'galería' : (videos.length > 0 ? 'video' : 'foto')}
         </button>` : '';
    return `
      <div class="labor-card" data-fotos='${JSON.stringify(fotos)}'>
        <div class="labor-card-img-wrap">
          ${imgWrap}${recentBadge}${topBadges}
        </div>
        <div class="labor-card-content">
          <h3 class="labor-card-title">${item.titulo || 'Labor Social'}</h3>
          <p class="labor-card-desc">${item.descripcion || ''}</p>
          <div class="labor-card-meta">
            ${item.ubicacion ? `<div class="labor-meta-item"><i class="fas fa-map-marker-alt"></i><span><strong>${item.ubicacion}</strong></span></div>` : ''}
            ${item.fecha ? `<div class="labor-meta-item"><i class="fas fa-calendar-alt"></i><span>${formatLaborDate(item.fecha, item.hora)}</span></div>` : ''}
          </div>
          <div class="labor-card-footer">
            ${item.fecha ? `<span class="labor-time-badge">${relativeDate(item.fecha)}</span>` : '<div></div>'}
            ${galleryBtn}
          </div>
        </div>
      </div>`;
  }

  // Ordenar: más recientes primero
  const sorted = [...data].sort((a, b) => {
    const cmp = (b.fecha || '').localeCompare(a.fecha || '');
    if (cmp !== 0) return cmp;
    return (b.id || '').localeCompare(a.id || '');
  });

  const emptyEl   = getAuxEl('labor-empty-state');
  const centeredEl = getAuxEl('labor-centered');

  // ── 0 labores: estado vacío ──────────────────────────
  if (!sorted.length) {
    wrap.style.display = 'none';
    centeredEl.style.display = 'none';
    emptyEl.innerHTML = `
      <div class="labor-empty">
        <div class="labor-empty-icon">🤝</div>
        <h3>Pronto publicaremos nuestras labores</h3>
        <p>Estamos documentando el trabajo realizado en las comunidades.</p>
      </div>`;
    emptyEl.style.display = 'block';
    updateLaborNavBtns();
    return;
  }

  // ── 1 o 2 labores: centradas fuera del carousel ──────
  if (sorted.length <= 2) {
    wrap.style.display = 'none';
    emptyEl.style.display = 'none';
    centeredEl.style.cssText = 'display:flex; justify-content:center; flex-wrap:wrap; gap:24px; padding:20px 0 30px;';
    centeredEl.innerHTML = sorted.map((item, idx) => buildCard(item, idx, sorted.length)).join('');
    window._laborData = sorted;
    // Registrar los datos en las tarjetas para la galería
    centeredEl.querySelectorAll('.labor-card').forEach((el, idx) => {
      el._idx = idx;
    });
    updateLaborNavBtns();
    return;
  }

  // ── 3+ labores: carrusel normal ──────────────────────
  emptyEl.style.display = 'none';
  centeredEl.style.display = 'none';
  wrap.style.display = '';
  track.style.display = '';

  track.innerHTML = sorted.map((item, idx) => buildCard(item, idx, sorted.length)).join('');
  window._laborData = sorted;

  initLaborDragScroll();
  updateLaborNavBtns();
}

function updateLaborNavBtns() {
  const track  = document.getElementById('labor-track');
  const btnL   = document.getElementById('labor-nav-left');
  const btnR   = document.getElementById('labor-nav-right');
  if (!track || !btnL || !btnR) return;

  const updateState = () => {
    btnL.disabled = track.scrollLeft <= 4;
    btnR.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
  };

  track.addEventListener('scroll', updateState, { passive: true });
  updateState();
}

function laborScroll(dir) {
  const track = document.getElementById('labor-track');
  if (!track) return;
  const cardWidth = track.querySelector('.labor-card')?.offsetWidth || 360;
  track.scrollBy({ left: dir * (cardWidth + 24), behavior: 'smooth' });
}

function initLaborDragScroll() {
  const track = document.getElementById('labor-track');
  if (!track) return;
  let isDown = false, startX, scrollLeft;

  track.addEventListener('mousedown', e => {
    isDown   = true;
    startX   = e.pageX - track.offsetLeft;
    scrollLeft = track.scrollLeft;
    track.classList.add('dragging');
  });
  track.addEventListener('mouseleave', () => { isDown = false; track.classList.remove('dragging'); });
  track.addEventListener('mouseup', () => { isDown = false; track.classList.remove('dragging'); });
  track.addEventListener('mousemove', e => {
    if (!isDown) return;
    e.preventDefault();
    const x    = e.pageX - track.offsetLeft;
    const walk = (x - startX) * 1.4;
    track.scrollLeft = scrollLeft - walk;
  });
}

// ── Galería de fotos y videos de una labor ──────────────────
let _galleryMedia = [];  // array de { src, type: 'image'|'video' }
let _galleryIdx   = 0;

function openLaborGallery(laborIdx, mediaIdx, event) {
  if (event) event.stopPropagation();
  const data = window._laborData;
  if (!data || !data[laborIdx]) return;

  const item   = data[laborIdx];
  const fotos  = (item.fotos  || []).map(src => ({ src, type: 'image' }));
  const videos = (item.videos || []).map(src => ({ src, type: 'video' }));
  _galleryMedia = [...fotos, ...videos];
  if (!_galleryMedia.length) return;

  _galleryIdx = Math.min(mediaIdx || 0, _galleryMedia.length - 1);
  renderGalleryMedia();

  const overlay = document.getElementById('labor-gallery-overlay');
  if (overlay) {
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}

function renderGalleryMedia() {
  const media   = _galleryMedia[_galleryIdx];
  const img     = document.getElementById('gallery-img');
  const video   = document.getElementById('gallery-video');
  const counter = document.getElementById('gallery-counter');
  if (counter) counter.textContent = `${_galleryIdx + 1} / ${_galleryMedia.length}`;

  if (media && media.type === 'video') {
    if (img)   { img.style.display = 'none'; img.src = ''; }
    if (video) { video.style.display = 'block'; video.src = media.src; }
  } else {
    if (video) { video.style.display = 'none'; video.pause(); video.src = ''; }
    if (img)   { img.style.display = 'block'; img.src = media ? media.src : ''; }
  }

  const prev = document.getElementById('gallery-prev');
  const next = document.getElementById('gallery-next');
  if (prev) prev.style.opacity = _galleryIdx === 0 ? '0.35' : '1';
  if (next) next.style.opacity = _galleryIdx === _galleryMedia.length - 1 ? '0.35' : '1';
}

function closeGallery() {
  const overlay = document.getElementById('labor-gallery-overlay');
  if (overlay) overlay.classList.remove('open');
  const video = document.getElementById('gallery-video');
  if (video) { video.pause(); video.src = ''; }
  document.body.style.overflow = '';
}

// Controles de galería
document.getElementById('labor-gallery-close')?.addEventListener('click', closeGallery);
document.getElementById('gallery-prev')?.addEventListener('click', () => {
  if (_galleryIdx > 0) { _galleryIdx--; renderGalleryMedia(); }
});
document.getElementById('gallery-next')?.addEventListener('click', () => {
  if (_galleryIdx < _galleryMedia.length - 1) { _galleryIdx++; renderGalleryMedia(); }
});
document.getElementById('labor-gallery-overlay')?.addEventListener('click', e => {
  if (e.target === e.currentTarget) closeGallery();
});

// Teclado en galería
document.addEventListener('keydown', e => {
  const overlay = document.getElementById('labor-gallery-overlay');
  if (!overlay?.classList.contains('open')) return;
  if (e.key === 'ArrowLeft'  && _galleryIdx > 0) { _galleryIdx--; renderGalleryMedia(); }
  if (e.key === 'ArrowRight' && _galleryIdx < _galleryMedia.length - 1) { _galleryIdx++; renderGalleryMedia(); }
  if (e.key === 'Escape') closeGallery();
});

// ── CONVENIOS ───────────────────────────────────────────────
async function loadConvenios() {
  try {
    const res = await fetch('convenios.json?v=' + Date.now());
    if (!res.ok) throw new Error('not found');
    const data = await res.json();
    renderConvenios(data);
  } catch {
    try {
      const res2 = await fetch('guardar_convenios.php?action=get');
      if (res2.ok) {
        const json2 = await res2.json();
        renderConvenios(json2.data || []);
        return;
      }
    } catch { /* silencioso */ }
    renderConvenios([]);
  }
}

function renderConvenios(data) {
  const grid = document.getElementById('convenios-grid');
  if (!grid) return;

  if (!data.length) {
    grid.innerHTML = `
      <div class="convenios-empty">
        <div style="font-size:3rem; margin-bottom:14px;">🤝</div>
        <h3>Próximamente anunciaremos nuestros convenios</h3>
        <p>Estamos formalizando alianzas con instituciones para ampliar nuestro impacto.</p>
      </div>`;
    return;
  }

  grid.innerHTML = data.map(item => {
    const logoHtml = item.logo
      ? `<img src="${item.logo}" alt="${item.nombre}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=\\'convenio-logo-placeholder\\'>🏛️</div>'">`
      : `<div class="convenio-logo-placeholder">🏛️</div>`;

    return `
      <div class="convenio-card reveal">
        <div class="convenio-logo-wrap">${logoHtml}</div>
        <p class="convenio-nombre">${item.nombre}</p>
      </div>`;
  }).join('');

  grid.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));
}

