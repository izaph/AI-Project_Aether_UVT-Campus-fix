from fastapi import FastAPI
from pydantic import BaseModel

app = FastAPI()

class TicketRequest(BaseModel):
    description: str

class ClassificationResponse(BaseModel):
    category: str
    confidence: float

@app.post("/classify", response_model=ClassificationResponse)
def classify(req: TicketRequest):
    # MOCK - returns hardcoded response until real model is trained
    return {
        "category": "Echipamente IT",
        "confidence": 0.91
    }

@app.get("/health")
def health():
    return {"status": "ok"}
