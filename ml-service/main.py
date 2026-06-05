from fastapi import FastAPI
from pydantic import BaseModel
import joblib
import os

app = FastAPI()

# Load model at startup
MODEL_PATH = "model.joblib"
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

@app.post("/classify", response_model=ClassificationResponse)
def classify(req: TicketRequest):
    if model is None:
        # Graceful degradation - no model available
        return {
            "category": "Necunoscut",
            "confidence": 0.0,
            "ai_available": False
        }
    
    probs = model.predict_proba([req.description])[0]
    idx = probs.argmax()
    confidence = float(probs[idx])
    
    # Only suggest if confidence above threshold
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

@app.get("/health")
def health():
    return {
        "status": "ok",
        "model_loaded": model is not None
    }
