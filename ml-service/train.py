import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split
import joblib

# Load dataset
df = pd.read_csv("dataset_tichete.csv")

# Split
X_train, X_test, y_train, y_test = train_test_split(
    df["text"], df["categorie"], test_size=0.2, random_state=42
)

# Train
pipeline = Pipeline([
    ("tfidf", TfidfVectorizer(ngram_range=(1, 2), min_df=1)),
    ("clf", LogisticRegression(max_iter=1000)),
])
pipeline.fit(X_train, y_train)

# Evaluate
accuracy = pipeline.score(X_test, y_test)
print(f"Test accuracy: {accuracy:.2%}")

# Save model
joblib.dump(pipeline, "model.joblib")
print("Model saved: model.joblib")
