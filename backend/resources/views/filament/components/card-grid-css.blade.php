<style>
.cb-card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px}
.cb-card{position:relative;border:2px solid #e2e8f0;border-radius:12px;padding:16px;cursor:pointer;transition:all .2s ease;background:#fff;user-select:none}
.cb-card:hover{border-color:#5eead4;box-shadow:0 2px 8px rgba(0,48,56,.08)}
.cb-card.selected{border-color:#0f766e;background:#f0fdfa;box-shadow:0 0 0 3px rgba(15,118,110,.15)}
.cb-card-icon{width:40px;height:40px;border-radius:10px;background:#f0fdfa;color:#0f766e;display:flex;align-items:center;justify-content:center;margin-bottom:10px;font-size:18px}
.cb-card.selected .cb-card-icon{background:#0f766e;color:#fff}
.cb-card-title{font-size:15px;font-weight:600;color:#003038;margin-bottom:2px}
.cb-card-subtitle{font-size:13px;color:#64748b;line-height:1.4}
.cb-card-badge{display:inline-block;margin-top:8px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:6px;background:#f0fdfa;color:#0f766e}
.cb-card.selected .cb-card-badge{background:#0f766e;color:#fff}
.cb-card-badge.low{background:#fef3c7;color:#92400e}
.cb-card.selected .cb-card-badge.low{background:#0f766e;color:#fff}
.cb-card-check{position:absolute;top:8px;right:8px;width:22px;height:22px;border-radius:50%;background:#0f766e;color:#fff;display:none;align-items:center;justify-content:center;font-size:12px;line-height:1}
.cb-card.selected .cb-card-check{display:flex}
.cb-card-grid.types{grid-template-columns:repeat(auto-fill,minmax(300px,1fr))}
.cb-card.type-card{padding:24px}
.cb-card.type-card .cb-card-icon{width:48px;height:48px;font-size:22px;border-radius:12px}
.cb-card.type-card .cb-card-title{font-size:17px;margin-bottom:4px}
.cb-card.type-card .cb-card-subtitle{font-size:14px}
.fi-section-content{padding:0!important}
</style>
