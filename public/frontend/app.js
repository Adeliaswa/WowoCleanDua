const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api",
  headers: {
    "Content-Type": "application/json",
    "Accept": "application/json"
  }
});

const containerList = document.getElementById("containerList");
const totalWeightEl = document.getElementById("totalWeight");
const form = document.getElementById("containerForm");

function clearErrors() {
  document.getElementById("error_container_id").innerText = "";
  document.getElementById("error_waste_type").innerText = "";
  document.getElementById("error_weight_kg").innerText = "";
  document.getElementById("error_status").innerText = "";
}

function showErrors(errors) {
  clearErrors();

  if (errors.container_id) {
    document.getElementById("error_container_id").innerText = errors.container_id[0];
  }
  if (errors.waste_type) {
    document.getElementById("error_waste_type").innerText = errors.waste_type[0];
  }
  if (errors.weight_kg) {
    document.getElementById("error_weight_kg").innerText = errors.weight_kg[0];
  }
  if (errors.status) {
    document.getElementById("error_status").innerText = errors.status[0];
  }
}

function calculateTotalWeight(data) {
  const total = data.reduce((sum, item) => sum + Number(item.weight_kg), 0);
  totalWeightEl.innerText = total;
}

async function loadContainers() {
  try {
    const response = await api.get("/containers");
    renderContainers(response.data);
    calculateTotalWeight(response.data);
  } catch (error) {
    console.error(error);
    alert("Gagal mengambil data kontainer");
  }
}

async function renderContainers(data) {
  containerList.innerHTML = "";

  if (data.length === 0) {
    containerList.innerHTML = "<p>Tidak ada data kontainer.</p>";
    totalWeightEl.innerText = "0";
    return;
  }

  for (const item of data) {
    const logsHtml = await getLogsHtml(item.id);

    const card = document.createElement("div");
    card.className = "item-card";

    card.innerHTML = `
      <h3>${item.container_id}</h3>
      <p><strong>Type:</strong> ${item.waste_type}</p>
      <p><strong>Weight:</strong> ${item.weight_kg} kg</p>
      <p>
        <strong>Status:</strong>
        <span class="badge ${item.status === "Active" ? "badge-active" : "badge-archived"}">
          ${item.status}
        </span>
      </p>

      <div style="display:flex; gap:10px; margin-top:10px;">
        <button class="btn-warning" onclick="archiveContainer(${item.id})">Archive</button>
        <button class="btn-danger" onclick="deleteContainer(${item.id})">Delete</button>
      </div>

      <div class="logs">
        <strong>Tracking Logs:</strong>
        ${logsHtml}
      </div>
    `;

    containerList.appendChild(card);
  }
}

async function getLogsHtml(id) {
  try {
    const response = await api.get(`/containers/${id}/logs`);
    const logs = response.data.tracking_logs;

    if (!logs || logs.length === 0) {
      return "<p>Tidak ada log.</p>";
    }

    return `
      <ul>
        ${logs.map(log => `
          <li>
            <strong>${log.location}</strong> - ${log.timestamp}<br>
            ${log.description}
          </li>
        `).join("")}
      </ul>
    `;
  } catch (error) {
    return "<p>Gagal mengambil log.</p>";
  }
}

form.addEventListener("submit", async function (e) {
  e.preventDefault();
  clearErrors();

  const payload = {
    container_id: document.getElementById("container_id").value,
    waste_type: document.getElementById("waste_type").value,
    weight_kg: document.getElementById("weight_kg").value,
    status: document.getElementById("status").value
  };

  try {
    await api.post("/containers", payload);
    form.reset();
    loadContainers();
    alert("Data berhasil ditambahkan");
  } catch (error) {
    if (error.response && error.response.status === 422) {
      showErrors(error.response.data.errors);
    } else {
      alert("Terjadi kesalahan saat menyimpan data");
    }
  }
});

async function archiveContainer(id) {
  try {
    await api.patch(`/containers/${id}/archive`);
    alert("Kontainer berhasil di-archive");
    loadContainers();
  } catch (error) {
    if (error.response && error.response.status === 404) {
      alert("Data tidak ditemukan");
    } else {
      alert("Gagal mengubah status");
    }
  }
}

async function deleteContainer(id) {
  const confirmDelete = confirm("Yakin ingin menghapus kontainer ini?");
  if (!confirmDelete) return;

  try {
    await api.delete(`/containers/${id}`);
    alert("Kontainer berhasil dihapus");
    loadContainers();
  } catch (error) {
    if (error.response && error.response.status === 404) {
      alert("Data tidak ditemukan");
    } else {
      alert("Gagal menghapus data");
    }
  }
}

async function applyFilter() {
  const type = document.getElementById("filterType").value;
  const minWeight = document.getElementById("minWeight").value;

  const params = new URLSearchParams();
  if (type) params.append("type", type);
  if (minWeight) params.append("min_weight", minWeight);

  try {
    const response = await api.get(`/containers/search?${params.toString()}`);
    renderContainers(response.data);
    calculateTotalWeight(response.data);
  } catch (error) {
    console.error(error);
    alert("Gagal memfilter data");
  }
}

loadContainers();