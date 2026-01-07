document.addEventListener('DOMContentLoaded', () => {
    const memberList = document.getElementById('member-list'); // Actually container for families
    const filterBtns = document.querySelectorAll('.filter-btn');
    let dbData = { families: [], members: [] };

    // ฟังก์ชันช่วยจัดรูปแบบวันที่ (Date Formatter)
    // เปลี่ยนจาก Unix timestamp เป็น "15 Feb 2026"
    function formatDueDate(lastPaidDate) {
        if (!lastPaidDate) return 'N/A';

        const paidDate = new Date(lastPaidDate);
        // บวกไป 30 วัน (Add 30 Days)
        const dueDate = new Date(paidDate.getTime() + (30 * 24 * 60 * 60 * 1000));

        const day = dueDate.getDate();
        const month = dueDate.toLocaleString('default', { month: 'short' });
        const year = dueDate.getFullYear();

        return `${day} ${month}`;
    }

    // ฟังก์ชันดึงข้อมูลจาก API (Fetch Data)
    // ดึงข้อมูลจาก PHP API
    async function fetchData() {
        try {
            const response = await fetch('api.php?action=data');
            dbData = await response.json();
            renderFamilies(dbData);
        } catch (error) {
            console.error('Error fetching data:', error);
            memberList.innerHTML = '<p style="text-align:center; color: #ef4444;">Failed to load data.</p>';
        }
    }

    // ฟังก์ชันแสดงผลบนหน้าจอ (Render Families)
    function renderFamilies(data) {
        memberList.innerHTML = '';
        const { families, members } = data;

        if (families.length === 0) {
            memberList.innerHTML = '<div style="text-align:center; color: #94a3b8; grid-column: 1/-1;">No families found.</div>';
            return;
        }

        families.forEach((family, index) => {
            const familyMembers = members.filter(m => m.familyId === family.id);
            const isSpotify = family.type === 'spotify';
            const iconClass = isSpotify ? 'fa-spotify' : 'fa-youtube';
            const familyColor = isSpotify ? 'var(--spotify-green)' : 'var(--youtube-red)';

            // สร้างส่วนแสดง Family
            const familySection = document.createElement('div');
            familySection.className = 'family-section';
            familySection.style.animationDelay = `${index * 0.1}s`;

            // ส่วนหัว (Header)
            const header = document.createElement('div');
            header.className = 'family-header';
            header.innerHTML = `
                <div class="family-title">
                    <i class="fa-brands ${iconClass}" style="color: ${familyColor}; font-size: 1.5rem;"></i>
                    <h2>${family.name}</h2>
                </div>
                <div class="family-stats">
                    ${familyMembers.length} / ${family.maxMembers} Members
                </div>
            `;
            familySection.appendChild(header);

            // Grid รายชื่อสมาชิก
            const grid = document.createElement('div');
            grid.className = 'member-grid';

            if (familyMembers.length === 0) {
                grid.innerHTML = '<div style="color: rgba(255,255,255,0.3); padding: 1rem;">No members yet.</div>';
            } else {
                familyMembers.forEach(member => {
                    const card = document.createElement('div');
                    card.className = 'member-card';
                    const statusClass = member.status === 'paid' ? 'status-paid' : 'status-unpaid';
                    const statusText = member.status === 'paid' ? 'PAID' : 'UNPAID';

                    // คำนวณวันจ่ายเงินครั้งถัดไป (Next Due Date)
                    const dueDateText = member.status === 'paid'
                        ? `<div class="due-date"><i class="fa-regular fa-clock"></i> Next: ${formatDueDate(member.lastPaidDate)}</div>`
                        : '';

                    card.innerHTML = `
                        <div class="member-info">
                            <div>
                                <div class="member-name">${member.name}</div>
                                <div class="member-details">${member.month}</div>
                                ${dueDateText}
                            </div>
                        </div>
                        <div class="status-badge ${statusClass}">
                            ${statusText}
                        </div>
                    `;
                    grid.appendChild(card);
                });
            }

            familySection.appendChild(grid);
            memberList.appendChild(familySection);
        });
    }

    // ฟังก์ชันกรอง (Filter)
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const filter = btn.getAttribute('data-filter');
            if (filter === 'all') {
                renderFamilies(dbData);
            } else {
                const filteredFamilies = dbData.families.filter(f => f.type === filter);
                renderFamilies({ families: filteredFamilies, members: dbData.members });
            }
        });
    });

    fetchData(); // เริ่มทำงาน
});
