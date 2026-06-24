from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import joblib
import os
import json
from datetime import datetime

app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

MODEL_PATH = "model.joblib"
FEEDBACK_PATH = "feedback_log.json"
model = None

if os.path.exists(MODEL_PATH):
    model = joblib.load(MODEL_PATH)
    print("Model loaded successfully")
else:
    print("WARNING: model.joblib not found, using fallback")


class TicketRequest(BaseModel):
    description: str

class ClassificationResponse(BaseModel):
    category: str
    confidence: float
    ai_available: bool

class FeedbackRequest(BaseModel):
    description: str
    ai_category: str
    user_category: str
    confidence: float = 0.0
    ticket_id: int = 0

class FeedbackStats(BaseModel):
    total: int
    correct: int
    accuracy: float
    per_category: dict


@app.post("/classify", response_model=ClassificationResponse)
def classify(req: TicketRequest):
    if model is None:
        return {
            "category": "Necunoscut",
            "confidence": 0.0,
            "ai_available": False
        }

    probs = model.predict_proba([req.description])[0]
    idx = probs.argmax()
    confidence = float(probs[idx])

    if confidence < 0.35:
        return {
            "category": "Necunoscut",
            "confidence": confidence,
            "ai_available": True
        }

    return {
        "category": model.classes_[idx],
        "confidence": confidence,
        "ai_available": True
    }


@app.post("/feedback")
def feedback(req: FeedbackRequest):
    entry = {
        "timestamp": datetime.now().isoformat(),
        "description": req.description,
        "ai_category": req.ai_category,
        "user_category": req.user_category,
        "confidence": req.confidence,
        "ticket_id": req.ticket_id,
        "correct": req.ai_category == req.user_category,
    }

    log = []
    if os.path.exists(FEEDBACK_PATH):
        with open(FEEDBACK_PATH, "r", encoding="utf-8") as f:
            try:
                log = json.load(f)
            except json.JSONDecodeError:
                log = []

    log.append(entry)

    with open(FEEDBACK_PATH, "w", encoding="utf-8") as f:
        json.dump(log, f, ensure_ascii=False, indent=2)

    return {"status": "saved", "total_feedback": len(log)}


@app.get("/feedback/stats", response_model=FeedbackStats)
def feedback_stats():
    if not os.path.exists(FEEDBACK_PATH):
        return {"total": 0, "correct": 0, "accuracy": 0.0, "per_category": {}}

    with open(FEEDBACK_PATH, "r", encoding="utf-8") as f:
        try:
            log = json.load(f)
        except json.JSONDecodeError:
            return {"total": 0, "correct": 0, "accuracy": 0.0, "per_category": {}}

    total = len(log)
    correct = sum(1 for e in log if e.get("correct"))
    accuracy = (correct / total * 100) if total > 0 else 0.0

    per_cat = {}
    for e in log:
        cat = e.get("ai_category", "Necunoscut")
        if cat not in per_cat:
            per_cat[cat] = {"total": 0, "correct": 0}
        per_cat[cat]["total"] += 1
        if e.get("correct"):
            per_cat[cat]["correct"] += 1

    return {
        "total": total,
        "correct": correct,
        "accuracy": round(accuracy, 1),
        "per_category": per_cat,
    }


@app.get("/health")
def health():
    return {
        "status": "ok",
        "model_loaded": model is not None
    }
