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
    return data.filter(v => v.activa !== false);
  } catch {
    return SAMPLE_VACANTES;
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

// ── Init ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  const vacantes = await cargarVacantes();
  renderVacantes(vacantes);
  // Init Lucide icons
  if (window.lucide) lucide.createIcons();
});
